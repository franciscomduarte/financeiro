<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AvisarFimDoTesteAction;
use App\Enums\RoleUsuario;
use App\Enums\StatusClinica;
use App\Livewire\TaxasCartaoIndex;
use App\Mail\ConfirmarEmailMail;
use App\Mail\FimDoTesteMail;
use App\Mail\NovaClinicaCadastradaMail;
use App\Models\Clinica;
use App\Models\DocumentoCategoria;
use App\Models\Paciente;
use App\Models\TaxaCartao;
use App\Models\Transacao;
use App\Models\User;
use App\Services\VerificacaoEmailService;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Multiclínica — fase 3: "Assine já", teste grátis de 14 dias e modo somente leitura. */
class AutocadastroTest extends TestCase
{
    use RefreshDatabase;

    private function dados(array $extra = []): array
    {
        return $extra + [
            'clinica'               => 'Clínica Bem Estar',
            'nome'                  => 'Joana Souza',
            'email'                 => 'Joana@BemEstar.com',
            'celular'               => '(61) 99999-0000',
            'password'              => 'senha1234',
            'password_confirmation' => 'senha1234',
        ];
    }

    /** Clínica em teste com um admin; $diasParaFim negativo = teste já encerrado. */
    private function clinicaEmTeste(int $diasParaFim, bool $emailConfirmado = true): array
    {
        $clinica = $this->novaClinica('Clínica em Teste');
        $clinica->update(['status' => StatusClinica::Teste, 'teste_ate' => today()->addDays($diasParaFim)]);

        $admin = User::factory()->semClinica()->create(['role' => 'admin', 'email_verified_at' => $emailConfirmado ? now() : null]);
        $admin->clinicas()->attach($clinica->id, ['papel' => RoleUsuario::Admin->value]);

        return [$clinica->fresh(), $admin];
    }

    // ─── Cadastro ─────────────────────────────────────────────────

    public function test_cadastro_cria_clinica_em_teste_admin_e_padroes_e_ja_entra(): void
    {
        Mail::fake();
        config(['plataforma.email' => 'dono@plataforma.com']);

        $this->post('/assine', $this->dados())->assertRedirect(route('dashboard'));

        $user    = User::where('email', 'joana@bemestar.com')->firstOrFail();
        $clinica = $user->clinicas()->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(StatusClinica::Teste, $clinica->status);
        $this->assertSame(today()->addDays(13)->toDateString(), $clinica->teste_ate->toDateString());
        $this->assertSame(14, $clinica->diasRestantesTeste());
        $this->assertSame(RoleUsuario::Admin, $user->papelNa($clinica));
        $this->assertNull($user->email_verified_at);
        $this->assertSame('(61) 99999-0000', $clinica->telefone);

        app(ClinicaAtual::class)->executarComo($clinica, function (): void {
            $this->assertSame(8, DocumentoCategoria::count());
            $this->assertSame(15, TaxaCartao::count());
            $this->assertSame(0, (int) TaxaCartao::sum('percentual'));
            $this->assertSame(0, Paciente::count());
        });
        $this->assertSame(0, app(ClinicaAtual::class)->executarComo($this->clinica, fn () => DocumentoCategoria::count()));

        Mail::assertQueued(ConfirmarEmailMail::class, fn ($m) => $m->hasTo('joana@bemestar.com'));
        Mail::assertQueued(NovaClinicaCadastradaMail::class, fn ($m) => $m->hasTo('dono@plataforma.com'));

        $this->get('/dashboard')->assertOk()->assertSee('faltam 14 dias')->assertSee('Confirme seu e-mail');
    }

    public function test_cadastro_valida_dados_e_email_repetido(): void
    {
        User::factory()->create(['email' => 'joana@bemestar.com']);

        $this->post('/assine', $this->dados(['password_confirmation' => 'outra123']))
            ->assertSessionHasErrors(['email', 'password']);
        $this->post('/assine', $this->dados(['email' => 'novo@x.com', 'celular' => '123']))
            ->assertSessionHasErrors(['celular']);

        $this->assertSame(1, Clinica::count());
    }

    public function test_robo_que_preenche_campo_escondido_e_recusado(): void
    {
        $this->post('/assine', $this->dados(['site' => 'http://spam.com']))->assertSessionHasErrors(['site']);
        $this->assertSame(0, User::where('email', 'joana@bemestar.com')->count());
    }

    public function test_cadastro_tem_limite_de_tentativas(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/assine', $this->dados(['email' => 'invalido']));
        }

        $this->post('/assine', $this->dados())->assertStatus(429);
    }

    public function test_paginas_publicas(): void
    {
        $this->get('/assine')->assertOk()->assertSee('Começar meu teste grátis');
        $this->get('/login')->assertOk()->assertSee('Assine já');
    }

    // ─── Confirmação de e-mail ────────────────────────────────────

    public function test_link_confirma_o_email_sem_precisar_de_login(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $link = app(VerificacaoEmailService::class)->link($user);

        $this->get($link)->assertRedirect(route('login'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_link_adulterado_ou_de_outro_email_nao_confirma(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $link = app(VerificacaoEmailService::class)->link($user);

        $this->get($link . 'x')->assertForbidden();

        $user->update(['email' => 'trocou@x.com']);
        $this->get($link)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_reenviar_link(): void
    {
        Mail::fake();
        [, $admin] = $this->clinicaEmTeste(5, emailConfirmado: false);

        $this->actingAs($admin)->post(route('verificacao.reenviar'))->assertSessionHas('success');
        Mail::assertQueued(ConfirmarEmailMail::class);
    }

    // ─── Fim do teste: somente leitura ────────────────────────────

    public function test_ultimo_dia_ainda_permite_gravar(): void
    {
        [$clinica] = $this->clinicaEmTeste(0);

        $this->assertFalse($clinica->somenteLeitura());
        $this->assertSame(1, $clinica->diasRestantesTeste());
        app(ClinicaAtual::class)->executarComo($clinica, fn () => Paciente::create(['nome' => 'Ok']));
        $this->assertSame(1, app(ClinicaAtual::class)->executarComo($clinica, fn () => Paciente::count()));
    }

    public function test_teste_encerrado_consulta_mas_nao_grava(): void
    {
        [$clinica, $admin] = $this->clinicaEmTeste(5);
        $paciente = app(ClinicaAtual::class)->executarComo($clinica, fn () => Paciente::create(['nome' => 'Maria']));
        $clinica->update(['teste_ate' => today()->subDay()]);

        $this->actingAs($admin)->get('/pacientes')->assertOk()->assertSee('Maria')->assertSee('modo somente leitura');

        app(ClinicaAtual::class)->executarComo($clinica->fresh(), function () use ($paciente): void {
            foreach ([
                fn () => Paciente::create(['nome' => 'Nova']),
                fn () => $paciente->update(['nome' => 'Alterada']),
                fn () => $paciente->delete(),
            ] as $gravacao) {
                try {
                    $gravacao();
                    $this->fail('Gravação deveria ter sido barrada.');
                } catch (\App\Exceptions\ClinicaSomenteLeituraException) {
                    // esperado
                }
            }
            $this->assertSame(['Maria'], Paciente::pluck('nome')->all());
        });
    }

    public function test_api_recusa_gravacao_com_teste_encerrado(): void
    {
        [$clinica, $admin] = $this->clinicaEmTeste(-1);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/transacoes')->assertOk();
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/transacoes', [
            'tipo' => 'entrada', 'fase' => 'operacao', 'categoria' => 'Massagem', 'descricao' => 'X',
            'valor_bruto' => 10, 'forma_pagamento' => 'pix', 'data_competencia' => '2026-10-01',
        ])->assertForbidden()->assertJsonPath('message', fn ($m) => str_contains($m, 'somente leitura'));

        $this->assertSame(0, app(ClinicaAtual::class)->executarComo($clinica, fn () => Transacao::count()));
    }

    public function test_livewire_mostra_o_motivo_em_vez_de_erro_generico(): void
    {
        [$clinica, $admin] = $this->clinicaEmTeste(5);
        app(ClinicaAtual::class)->executarComo($clinica, fn () => TaxaCartao::create(['modalidade' => 'pix', 'percentual' => 0, 'ativo' => true]));
        $clinica->update(['teste_ate' => today()->subDay()]);
        $this->actingAs($admin);
        app(ClinicaAtual::class)->definir($clinica->fresh());

        Livewire::test(TaxasCartaoIndex::class)
            ->call('salvarTodas')
            ->assertSet('flashErro', fn ($m) => str_contains((string) $m, 'somente leitura'));
    }

    public function test_teste_encerrado_sem_email_confirmado_pede_confirmacao(): void
    {
        [, $admin] = $this->clinicaEmTeste(-1, emailConfirmado: false);

        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('verificacao.aviso'));
        $this->actingAs($admin)->get(route('verificacao.aviso'))->assertOk()->assertSee('Reenviar o link');
    }

    public function test_tarefas_agendadas_ignoram_clinica_com_teste_encerrado(): void
    {
        [$encerrada] = $this->clinicaEmTeste(-1);
        $vistas = [];

        app(ClinicaAtual::class)->paraCadaClinica(function (Clinica $c) use (&$vistas): void {
            $vistas[] = $c->id;
        });

        $this->assertContains($this->clinica->id, $vistas);
        $this->assertNotContains($encerrada->id, $vistas);
    }

    public function test_clinica_ativa_nao_e_afetada(): void
    {
        $this->assertFalse($this->clinica->somenteLeitura());
        $this->assertNull($this->clinica->diasRestantesTeste());

        $this->actingAs(User::factory()->create(['role' => 'admin', 'email_verified_at' => null]))
            ->get('/dashboard')->assertOk()->assertDontSee('Teste grátis')->assertDontSee('Confirme seu e-mail');
    }

    // ─── Avisos de fim do teste ───────────────────────────────────

    public function test_avisos_saem_3_dias_antes_e_no_ultimo_dia_uma_vez_cada(): void
    {
        Mail::fake();
        [$clinica, $admin] = $this->clinicaEmTeste(3);
        $usuarioComum = User::factory()->semClinica()->create();
        $usuarioComum->clinicas()->attach($clinica->id, ['papel' => 'user']);

        $acao = app(AvisarFimDoTesteAction::class);
        $this->assertSame(1, $acao->execute());
        $this->assertSame(0, $acao->execute()); // não repete

        Mail::assertQueued(FimDoTesteMail::class, 1);
        Mail::assertQueued(FimDoTesteMail::class, fn ($m) => $m->hasTo($admin->email) && $m->diasParaFim === 3);

        $this->travelTo(now()->addDays(3));
        $this->assertSame(1, $acao->execute());
        $this->assertSame(0, $acao->execute());
        Mail::assertQueued(FimDoTesteMail::class, fn ($m) => $m->diasParaFim === 0
            && str_contains($m->envelope()->subject, 'último dia'));
        Mail::assertQueued(FimDoTesteMail::class, 2);
    }

    public function test_aviso_nao_sai_longe_do_fim(): void
    {
        Mail::fake();
        $this->clinicaEmTeste(10);

        $this->assertSame(0, app(AvisarFimDoTesteAction::class)->execute());
        Mail::assertNothingQueued();
    }
}
