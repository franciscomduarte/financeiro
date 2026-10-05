<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleUsuario;
use App\Livewire\AdminUsuarioIndex;
use App\Livewire\AgendamentoIndex;
use App\Livewire\PacienteIndex;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\Transacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** Perfis de acesso por clínica: admin, recepção, profissional e financeiro. */
class PerfisDeAcessoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(RoleUsuario $papel): User
    {
        return User::factory()->create(['role' => $papel->value]);
    }

    private function profissional(string $nome, ?User $user = null): Profissional
    {
        $p     = new Profissional(['nome' => $nome, 'email' => Str::slug($nome) . '@x.com', 'ativo' => true]);
        $p->id = (string) Str::uuid();
        $p->save();
        if ($user) {
            $p->forceFill(['user_id' => $user->id])->save();
        }

        return $p;
    }

    private function agendar(Profissional $prof, string $paciente): Agendamento
    {
        return Agendamento::create([
            'paciente_id'     => Paciente::create(['nome' => $paciente, 'anamnese' => "Alergia de {$paciente}"])->id,
            'profissional_id' => $prof->id,
            'procedimento_id' => Procedimento::firstOrCreate(['nome' => 'Botox'], ['duracao_minutos' => 60, 'valor' => 800, 'ativo' => true])->id,
            'inicio_em'       => now()->setTime(10, 0),
            'fim_em'          => now()->setTime(11, 0),
            'status'          => 'agendado',
        ]);
    }

    /** @return array<string, array{0: string, 1: array<int, string>, 2: array<int, string>}> */
    public static function acessos(): array
    {
        return [
            'recepção'     => ['recepcao', ['/agenda', '/pacientes', '/cobrancas'], ['/dashboard', '/transacoes', '/relatorio', '/estoque', '/fornecedores', '/agenda/configuracao', '/admin/usuarios']],
            'profissional' => ['profissional', ['/agenda', '/pacientes', '/estoque'], ['/dashboard', '/transacoes', '/cobrancas', '/fornecedores', '/admin/clinica']],
            'financeiro'   => ['financeiro', ['/dashboard', '/transacoes', '/transacoes/recorrencias', '/relatorio', '/cobrancas', '/taxas-cartao', '/estoque', '/fornecedores', '/documentos'], ['/agenda', '/pacientes', '/admin/usuarios']],
            'admin'        => ['admin', ['/dashboard', '/agenda', '/pacientes', '/transacoes', '/agenda/configuracao', '/admin/usuarios', '/admin/clinica'], []],
        ];
    }

    /** @dataProvider acessos */
    #[\PHPUnit\Framework\Attributes\DataProvider('acessos')]
    public function test_cada_perfil_abre_so_suas_areas(string $papel, array $liberadas, array $bloqueadas): void
    {
        $this->actingAs($this->usuario(RoleUsuario::from($papel)));

        foreach ($liberadas as $url) {
            $this->get($url)->assertOk();
        }
        foreach ($bloqueadas as $url) {
            $this->get($url)->assertRedirect()->assertSessionHas('error');
        }
    }

    public function test_tela_inicial_depende_do_perfil(): void
    {
        $this->actingAs($this->usuario(RoleUsuario::Recepcao))->get('/inicio')->assertRedirect(route('agenda.index'));
        $this->actingAs($this->usuario(RoleUsuario::Financeiro))->get('/inicio')->assertRedirect(route('dashboard'));
    }

    public function test_menu_mostra_so_o_que_o_perfil_acessa(): void
    {
        $this->actingAs($this->usuario(RoleUsuario::Recepcao))->get('/agenda')
            ->assertSee('Pacientes')->assertSee('Cobranças')
            ->assertDontSee('Lançamentos')->assertDontSee('Relatórios')->assertDontSee('Fornecedores');
    }

    public function test_api_respeita_o_perfil(): void
    {
        $this->actingAs($this->usuario(RoleUsuario::Recepcao), 'sanctum')->getJson('/api/v1/transacoes')->assertForbidden();
        $this->actingAs($this->usuario(RoleUsuario::Financeiro), 'sanctum')->getJson('/api/v1/transacoes')->assertOk();
        $this->actingAs($this->usuario(RoleUsuario::Recepcao), 'sanctum')->postJson('/api/v1/procedimentos', ['nome' => 'X'])->assertForbidden();
    }

    public function test_acoes_livewire_passam_pela_mesma_regra_da_pagina(): void
    {
        // Requisições do Livewire repetem os middlewares da rota da página (perfil e admin)
        $this->assertContains(\App\Http\Middleware\EnsureModulo::class, Livewire::getPersistentMiddleware());
    }

    // ─── Dados clínicos e financeiros na ficha do paciente ────────

    public function test_recepcao_nao_ve_nem_apaga_a_anamnese(): void
    {
        $paciente = Paciente::create(['nome' => 'Maria', 'anamnese' => 'Alergia a lidocaína']);
        $this->actingAs($this->usuario(RoleUsuario::Recepcao));

        Livewire::test(PacienteIndex::class)
            ->call('abrirDetalhe', $paciente->id)
            ->assertDontSee('Alergia a lidocaína')
            ->call('abrirModalEditar', $paciente->id)
            ->assertSet('anamnese', '')
            ->set('telefone', '(61) 99999-0000')
            ->call('atualizar')
            ->assertHasNoErrors();

        $this->assertSame('Alergia a lidocaína', $paciente->fresh()->anamnese);
        $this->assertSame('(61) 99999-0000', $paciente->fresh()->telefone);
    }

    public function test_profissional_ve_anamnese_mas_nao_o_financeiro_do_paciente(): void
    {
        $user = $this->usuario(RoleUsuario::Profissional);
        $agendamento = $this->agendar($this->profissional('Ana', $user), 'Joana');
        Transacao::factory()->create(['paciente_id' => $agendamento->paciente_id, 'descricao' => 'Pagamento sigiloso']);
        $this->actingAs($user);

        Livewire::test(PacienteIndex::class)
            ->call('abrirDetalhe', $agendamento->paciente_id)
            ->assertSee('Alergia de Joana')
            ->assertDontSee('Pagamento sigiloso');
    }

    // ─── Profissional: só a própria agenda e pacientes ────────────

    public function test_profissional_ve_so_a_propria_agenda_e_pacientes(): void
    {
        $user = $this->usuario(RoleUsuario::Profissional);
        $this->agendar($this->profissional('Ana', $user), 'Paciente da Ana');
        $outro = $this->agendar($this->profissional('Bruno'), 'Paciente do Bruno');
        $this->actingAs($user);

        $this->assertSame(['Paciente da Ana'], Paciente::pluck('nome')->all());
        $this->assertSame(1, Agendamento::count());

        Livewire::test(PacienteIndex::class)->assertSee('Paciente da Ana')->assertDontSee('Paciente do Bruno');
        // Paciente de outro profissional não abre (404)
        Livewire::test(PacienteIndex::class)->call('abrirDetalhe', $outro->paciente_id)->assertStatus(404);
    }

    public function test_profissional_sem_vinculo_nao_ve_nada(): void
    {
        $this->agendar($this->profissional('Bruno'), 'Paciente do Bruno');
        $this->actingAs($this->usuario(RoleUsuario::Profissional));

        $this->assertSame(0, Paciente::count());
        $this->assertSame(0, Agendamento::count());
    }

    public function test_agenda_do_profissional_fica_presa_a_ele(): void
    {
        $user = $this->usuario(RoleUsuario::Profissional);
        $ana  = $this->profissional('Ana', $user);
        $this->profissional('Bruno');
        $this->actingAs($user);

        Livewire::test(AgendamentoIndex::class)
            ->assertSet('filtroProfissionalId', $ana->id)
            ->set('filtroProfissionalId', '')
            ->assertSet('filtroProfissionalId', $ana->id);
    }

    public function test_admin_vincula_usuario_ao_profissional(): void
    {
        $ana = $this->profissional('Ana');
        $this->actingAs($this->usuario(RoleUsuario::Admin));

        Livewire::test(AdminUsuarioIndex::class)
            ->call('abrirModalNovo')
            ->set('nome', 'Ana Souza')->set('email', 'ana.souza@x.com')->set('senha', 'senha1234')
            ->set('role', 'profissional')
            ->call('salvarUsuario')
            ->assertHasErrors(['profissionalId'])
            ->set('profissionalId', $ana->id)
            ->call('salvarUsuario')
            ->assertHasNoErrors();

        $user = User::where('email', 'ana.souza@x.com')->firstOrFail();
        $this->assertSame($user->id, $ana->fresh()->user_id);
        $this->assertSame(RoleUsuario::Profissional, $user->papelNa($this->clinica));
    }

    public function test_usuarios_antigos_viraram_recepcao(): void
    {
        $this->assertSame('recepcao', \Illuminate\Support\Facades\DB::selectOne(
            "select column_default from information_schema.columns where table_name = 'clinica_user' and column_name = 'papel'"
        )->column_default === "'recepcao'::character varying" ? 'recepcao' : 'outro');
    }
}
