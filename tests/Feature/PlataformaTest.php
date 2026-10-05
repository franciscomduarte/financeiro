<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AcaoClinicaEvento;
use App\Enums\StatusClinica;
use App\Livewire\PacienteIndex;
use App\Livewire\PlataformaIndex;
use App\Models\Clinica;
use App\Models\ClinicaEvento;
use App\Models\Paciente;
use App\Models\User;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Multiclínica — fase 4: painel do dono da plataforma. */
class PlataformaTest extends TestCase
{
    use RefreshDatabase;

    private User $dono;
    private Clinica $outra;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dono = User::factory()->semClinica()->create(['name' => 'Dono']);
        $this->dono->forceFill(['is_super_admin' => true])->save();

        $this->outra = $this->novaClinica('Clínica Bem Estar');
        $this->outra->update(['status' => StatusClinica::Teste, 'teste_ate' => today()->addDays(3)]);
    }

    // ─── Acesso ───────────────────────────────────────────────────

    public function test_so_o_dono_acessa_o_painel(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/plataforma')->assertForbidden();
        $this->actingAs($this->dono)->get('/plataforma')->assertOk()->assertSee('Clínica Bem Estar')->assertSee('LC Estética');
    }

    public function test_dono_sem_clinica_vai_direto_para_o_painel(): void
    {
        $this->actingAs($this->dono)->get('/dashboard')->assertRedirect(route('plataforma.index'));
    }

    public function test_comando_da_e_tira_o_acesso(): void
    {
        $user = User::factory()->create(['email' => 'socio@exemplo.com']);

        $this->artisan('plataforma:super-admin', ['email' => 'socio@exemplo.com'])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_super_admin);

        $this->artisan('plataforma:super-admin', ['email' => 'socio@exemplo.com', '--remover' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->is_super_admin);

        $this->artisan('plataforma:super-admin', ['email' => 'nao@existe.com'])->assertFailed();
    }

    public function test_super_admin_nao_pode_ser_definido_por_atribuicao_em_massa(): void
    {
        $user = User::factory()->create();
        $user->update(['is_super_admin' => true]);

        $this->assertFalse($user->fresh()->is_super_admin);
    }

    // ─── Indicadores e lista ──────────────────────────────────────

    public function test_indicadores_e_busca(): void
    {
        $this->actingAs($this->dono);

        Livewire::test(PlataformaIndex::class)
            ->assertViewHas('indicadores', fn ($i) => $i['total'] === 2 && $i['teste'] === 1 && $i['ativas'] === 1 && $i['testes_acabando'] === 1)
            ->set('busca', 'bem estar')
            ->assertSee('Clínica Bem Estar')
            ->assertDontSee('LC Estética')
            ->set('busca', 'nada-disso')
            ->assertSee('Nada encontrado com esses filtros');
    }

    // ─── Ações ────────────────────────────────────────────────────

    public function test_ativar_bloquear_desbloquear_e_registrar_historico(): void
    {
        $this->actingAs($this->dono);
        $tela = Livewire::test(PlataformaIndex::class)->call('abrir', $this->outra->id);

        $tela->call('ativar');
        $this->assertSame(StatusClinica::Ativa, $this->outra->fresh()->status);
        $this->assertNotNull($this->outra->fresh()->ativada_em);

        $tela->set('motivoBloqueio', 'não pagou')->call('bloquear');
        $this->assertSame(StatusClinica::Bloqueada, $this->outra->fresh()->status);

        $tela->call('desbloquear');
        $this->assertSame(StatusClinica::Ativa, $this->outra->fresh()->status); // já tinha assinado

        $acoes = ClinicaEvento::where('clinica_id', $this->outra->id)->orderBy('id')->pluck('acao')->all();
        $this->assertSame([AcaoClinicaEvento::Ativacao, AcaoClinicaEvento::Bloqueio, AcaoClinicaEvento::Desbloqueio], $acoes);
        $this->assertSame('não pagou', ClinicaEvento::where('acao', 'bloqueio')->first()->detalhes['motivo']);
        $this->assertSame($this->dono->id, ClinicaEvento::first()->user_id);
    }

    public function test_desbloquear_quem_nunca_assinou_volta_para_o_teste(): void
    {
        $this->actingAs($this->dono);
        Livewire::test(PlataformaIndex::class)->call('abrir', $this->outra->id)->call('bloquear')->call('desbloquear');

        $this->assertSame(StatusClinica::Teste, $this->outra->fresh()->status);
    }

    public function test_estender_teste_conta_do_fim_atual_ou_de_hoje(): void
    {
        $this->actingAs($this->dono);
        $this->outra->update(['aviso_teste_3_dias_em' => now()]);

        Livewire::test(PlataformaIndex::class)->call('abrir', $this->outra->id)->set('diasExtensao', 10)->call('estenderTeste')->assertHasNoErrors();
        $this->assertSame(today()->addDays(13)->toDateString(), $this->outra->fresh()->teste_ate->toDateString());
        $this->assertNull($this->outra->fresh()->aviso_teste_3_dias_em); // avisos valem para o novo prazo

        // Teste já encerrado: os dias contam a partir de hoje (hoje incluso)
        $this->outra->update(['teste_ate' => today()->subDays(5)]);
        Livewire::test(PlataformaIndex::class)->call('abrir', $this->outra->id)->set('diasExtensao', 7)->call('estenderTeste');
        $this->assertSame(today()->addDays(6)->toDateString(), $this->outra->fresh()->teste_ate->toDateString());
        $this->assertFalse($this->outra->fresh()->somenteLeitura());
    }

    public function test_extensao_invalida_e_recusada(): void
    {
        $this->actingAs($this->dono);

        Livewire::test(PlataformaIndex::class)->call('abrir', $this->outra->id)
            ->set('diasExtensao', 500)->call('estenderTeste')->assertHasErrors(['diasExtensao']);
    }

    public function test_cadastro_registra_evento(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->post('/assine', [
            'clinica' => 'Nova', 'nome' => 'Ana', 'email' => 'ana@nova.com', 'celular' => '(61) 99999-1111',
            'password' => 'senha1234', 'password_confirmation' => 'senha1234',
        ]);

        $clinica = Clinica::where('nome', 'Nova')->firstOrFail();
        $this->assertTrue($clinica->eventos()->where('acao', AcaoClinicaEvento::Cadastro->value)->exists());
    }

    // ─── Suporte ──────────────────────────────────────────────────

    public function test_suporte_ve_a_clinica_sem_conseguir_gravar(): void
    {
        app(ClinicaAtual::class)->executarComo($this->outra, fn () => Paciente::create(['nome' => 'Paciente da Bem Estar']));

        $this->actingAs($this->dono)->post(route('plataforma.suporte.entrar', $this->outra->id))->assertRedirect(route('inicio'));
        $this->assertTrue(ClinicaEvento::where('acao', 'suporte_entrada')->where('clinica_id', $this->outra->id)->exists());

        $this->get('/pacientes')->assertOk()->assertSee('Paciente da Bem Estar')->assertSee('Modo suporte');

        // HTTP (formulário/API) recusa gravação
        $this->post(route('voz.transacao'))->assertRedirect();
        $this->assertSame(1, app(ClinicaAtual::class)->executarComo($this->outra, fn () => Paciente::count()));
    }

    public function test_suporte_livewire_mostra_o_motivo(): void
    {
        $this->actingAs($this->dono);
        app(ClinicaAtual::class)->definirSuporte($this->outra);

        Livewire::test(PacienteIndex::class)
            ->set('nome', 'Nova')
            ->call('salvar')
            ->assertSet('flashErro', fn ($m) => str_contains((string) $m, 'Acesso de suporte'));

        $this->assertSame(0, app(ClinicaAtual::class)->executarComo($this->outra, fn () => Paciente::count()));
    }

    public function test_suporte_entra_mesmo_em_clinica_bloqueada_e_sai(): void
    {
        $this->outra->update(['status' => StatusClinica::Bloqueada]);

        $this->actingAs($this->dono)->post(route('plataforma.suporte.entrar', $this->outra->id));
        $this->get('/dashboard')->assertOk();

        $this->post(route('plataforma.suporte.sair'))->assertRedirect(route('plataforma.index'));
        $this->assertTrue(ClinicaEvento::where('acao', 'suporte_saida')->exists());
        $this->get('/dashboard')->assertRedirect(route('plataforma.index'));
    }

    public function test_quem_nao_e_dono_nao_entra_como_suporte(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('plataforma.suporte.entrar', $this->outra->id))->assertForbidden();
    }

    public function test_menu_mostra_o_painel_so_para_o_dono(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/dashboard')
            ->assertSee('Dados da clínica')->assertDontSee('Painel da plataforma');

        $this->dono->clinicas()->attach($this->clinica->id, ['papel' => 'admin']);
        $this->actingAs($this->dono)->get('/dashboard')->assertSee('Painel da plataforma');
    }

    public function test_ultimo_acesso_da_clinica_e_registrado(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();

        $this->assertNotNull($this->clinica->fresh()->ultimo_acesso_em);
    }
}
