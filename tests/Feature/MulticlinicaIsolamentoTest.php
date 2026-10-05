<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\ClinicaNaoDefinidaException;
use App\Livewire\AdminUsuarioIndex;
use App\Livewire\TransacaoIndex;
use App\Models\Clinica;
use App\Models\Cobranca;
use App\Models\Paciente;
use App\Models\Transacao;
use App\Models\User;
use App\Support\ClinicaAtual;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Livewire\Livewire;
use Tests\Fixtures\RegistraClinicaDoJob;
use Tests\TestCase;

/** Garante que nenhuma clínica enxerga ou altera dados de outra. */
class MulticlinicaIsolamentoTest extends TestCase
{
    use RefreshDatabase;

    private Clinica $outra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outra = $this->novaClinica('Clínica Bem Estar');
    }

    /** Executa $callback com a "outra" clínica ativa. */
    private function naOutra(callable $callback): mixed
    {
        return app(ClinicaAtual::class)->executarComo($this->outra, $callback);
    }

    private function transacao(string $descricao): Transacao
    {
        return Transacao::factory()->create(['descricao' => $descricao]);
    }

    // ─── Model / Global Scope ─────────────────────────────────────

    public function test_consultas_so_enxergam_a_clinica_ativa(): void
    {
        $minha = $this->transacao('Da LC');
        $dela  = $this->naOutra(fn () => $this->transacao('Da outra'));

        $this->assertSame($this->clinica->id, $minha->tenant_id);
        $this->assertSame($this->outra->id, $dela->tenant_id);

        $this->assertSame(['Da LC'], Transacao::pluck('descricao')->all());
        $this->assertNull(Transacao::find($dela->id));
        $this->assertSame(['Da outra'], $this->naOutra(fn () => Transacao::pluck('descricao')->all()));
    }

    public function test_tenant_id_nao_e_aceito_por_atribuicao_em_massa(): void
    {
        $p = Paciente::create(['nome' => 'Maria', 'tenant_id' => $this->outra->id]);

        $this->assertSame($this->clinica->id, $p->tenant_id);
    }

    public function test_sem_clinica_ativa_consulta_falha_em_vez_de_vazar(): void
    {
        app(ClinicaAtual::class)->definir(null);

        $this->expectException(ClinicaNaoDefinidaException::class);
        Transacao::count();
    }

    public function test_sem_clinica_ativa_gravacao_falha(): void
    {
        app(ClinicaAtual::class)->definir(null);

        $this->expectException(ClinicaNaoDefinidaException::class);
        Paciente::create(['nome' => 'Sem clínica']);
    }

    public function test_unicos_valem_por_clinica(): void
    {
        Paciente::create(['nome' => 'Ana', 'cpf' => '111.111.111-11']);
        $outraAna = $this->naOutra(fn () => Paciente::create(['nome' => 'Ana', 'cpf' => '111.111.111-11']));

        $this->assertNotNull($outraAna->id);
    }

    // ─── Validação (exists/unique por clínica) ────────────────────

    public function test_api_nao_aceita_id_de_outra_clinica(): void
    {
        $pacienteDela = $this->naOutra(fn () => Paciente::create(['nome' => 'Paciente dela']));

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/v1/transacoes', [
                'tipo' => 'entrada', 'fase' => 'operacao', 'categoria' => 'Massagem', 'descricao' => 'X',
                'valor_bruto' => 10, 'forma_pagamento' => 'pix', 'data_competencia' => '2026-10-01',
                'paciente_id' => $pacienteDela->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['paciente_id']);
    }

    // ─── HTTP: API e telas ────────────────────────────────────────

    public function test_api_lista_e_detalhe_isolados(): void
    {
        $this->transacao('Da LC');
        $dela = $this->naOutra(fn () => $this->transacao('Da outra'));
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/transacoes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.descricao', 'Da LC');

        $this->actingAs($user, 'sanctum')->getJson("/api/v1/transacoes/{$dela->id}")->assertNotFound();
    }

    public function test_api_nao_deixa_escolher_clinica_que_o_usuario_nao_acessa(): void
    {
        $this->naOutra(fn () => $this->transacao('Da outra'));

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->withHeader('X-Clinica-Id', $this->outra->id)
            ->getJson('/api/v1/transacoes')
            ->assertOk()
            ->assertJsonCount(0, 'data'); // header ignorado: usuário só tem a LC (sem transações)
    }

    public function test_tela_de_transacoes_so_mostra_a_clinica_ativa(): void
    {
        $this->transacao('Lançamento da LC');
        $this->naOutra(fn () => $this->transacao('Lançamento da outra'));

        Livewire::test(TransacaoIndex::class)
            ->assertSee('Lançamento da LC')
            ->assertDontSee('Lançamento da outra');
    }

    // ─── Clínica ativa (middleware, escolha, troca) ───────────────

    public function test_usuario_com_duas_clinicas_escolhe_e_troca(): void
    {
        $user = User::factory()->create();
        $user->clinicas()->attach($this->outra->id, ['papel' => 'user']);
        $this->naOutra(fn () => $this->transacao('Lançamento da outra'));

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('clinicas.escolher'));

        $this->actingAs($user)->post(route('clinicas.ativar', $this->outra->id))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/transacoes')->assertOk()->assertSee('Lançamento da outra');
    }

    public function test_nao_ativa_clinica_que_nao_e_do_usuario(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clinicas.ativar', $this->outra->id))->assertNotFound();
    }

    public function test_usuario_sem_clinica_ve_aviso(): void
    {
        $this->actingAs(User::factory()->semClinica()->create())
            ->get('/dashboard')
            ->assertForbidden()
            ->assertSee('Sem clínica vinculada');
    }

    public function test_clinica_bloqueada_nao_abre(): void
    {
        $user = User::factory()->create();
        $this->clinica->update(['status' => 'bloqueada']);

        $this->actingAs($user)->get('/dashboard')->assertForbidden()->assertSee('está bloqueada');
    }

    public function test_papel_de_admin_e_por_clinica(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->clinicas()->attach($this->outra->id, ['papel' => 'admin']);

        $this->assertFalse($user->isAdmin());
        $this->assertTrue($this->naOutra(fn () => $user->isAdmin()));
    }

    public function test_gestao_de_usuarios_so_ve_usuarios_da_clinica(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin LC']);
        $dela  = User::factory()->semClinica()->create(['name' => 'Usuária da outra']);
        $dela->clinicas()->attach($this->outra->id, ['papel' => 'admin']);

        $this->actingAs($admin);
        $tela = Livewire::test(AdminUsuarioIndex::class)
            ->assertSee('Admin LC')
            ->assertDontSee('Usuária da outra');

        $this->expectException(ModelNotFoundException::class);
        $tela->call('abrirModalEditar', (string) $dela->id);
    }

    public function test_incluir_email_existente_so_libera_acesso(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dela  = User::factory()->semClinica()->create(['email' => 'contadora@exemplo.com']);
        $dela->clinicas()->attach($this->outra->id, ['papel' => 'user']);

        $this->actingAs($admin);
        Livewire::test(AdminUsuarioIndex::class)
            ->set('nome', 'Contadora')
            ->set('email', 'contadora@exemplo.com')
            ->set('senha', 'outra-senha-123')
            ->set('role', 'user')
            ->call('salvarUsuario')
            ->assertHasNoErrors();

        $this->assertTrue($dela->fresh()->pertenceA($this->clinica));
        $this->assertTrue($dela->fresh()->pertenceA($this->outra));
        $this->assertSame(1, User::where('email', 'contadora@exemplo.com')->count());
    }

    // ─── Jobs, agendamentos e webhooks ────────────────────────────

    public function test_tarefa_agendada_roda_uma_vez_por_clinica(): void
    {
        $this->transacao('Da LC');
        $this->naOutra(fn () => $this->transacao('Da outra'));
        $vistas = [];

        app(ClinicaAtual::class)->paraCadaClinica(function (Clinica $c) use (&$vistas): void {
            $vistas[$c->nome] = Transacao::pluck('descricao')->all();
        });

        ksort($vistas);
        $this->assertSame(['Clínica Bem Estar' => ['Da outra'], 'LC Estética' => ['Da LC']], $vistas);
        $this->assertSame($this->clinica->id, app(ClinicaAtual::class)->id()); // restaurada
    }

    public function test_job_na_fila_herda_a_clinica_de_quem_despachou(): void
    {
        config(['queue.default' => 'database']);
        Cache::forget('clinica-do-job');

        $this->naOutra(fn () => RegistraClinicaDoJob::dispatch());
        app(ClinicaAtual::class)->definir(null);
        Context::flush();

        Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

        $this->assertSame($this->outra->id, Cache::get('clinica-do-job'));
        $this->assertNull(app(ClinicaAtual::class)->id()); // limpa depois do job
    }

    public function test_job_despachado_pelo_agendador_sai_com_a_clinica_de_cada_iteracao(): void
    {
        config(['queue.default' => 'database']);

        // Mesmo padrão de routes/console.php: arrow fn que retorna o PendingDispatch
        app(ClinicaAtual::class)->paraCadaClinica(fn () => RegistraClinicaDoJob::dispatch());

        $clinicasNosPayloads = \Illuminate\Support\Facades\DB::table('jobs')->orderBy('id')->pluck('payload')
            ->map(fn (string $p) => json_decode($p, true))
            ->map(fn (array $p) => unserialize($p['illuminate:log:context']['data']['tenant_id'] ?? 'N;'))
            ->all();

        sort($clinicasNosPayloads);
        $esperado = [$this->clinica->id, $this->outra->id];
        sort($esperado);
        $this->assertSame($esperado, $clinicasNosPayloads);
    }

    public function test_webhook_do_asaas_atualiza_a_cobranca_da_clinica_certa(): void
    {
        config(['asaas.webhook_token' => null]);
        $cobranca = $this->naOutra(function () {
            $p = Paciente::create(['nome' => 'Paciente dela']);

            return Cobranca::create([
                'paciente_id' => $p->id, 'asaas_id' => 'pay_123', 'valor' => 100,
                'vencimento' => '2026-10-10', 'mes_referencia' => '2026-10', 'status' => 'PENDING',
            ]);
        });

        $this->postJson('/api/webhook/asaas', ['event' => 'PAYMENT_RECEIVED', 'payment' => ['id' => 'pay_123']])
            ->assertOk();

        $this->assertSame('RECEIVED', $this->naOutra(fn () => Cobranca::find($cobranca->id)->status));
    }

    public function test_arquivos_vao_para_a_pasta_da_clinica(): void
    {
        $this->assertSame("clinicas/{$this->clinica->id}/documentos", app(ClinicaAtual::class)->pasta('documentos'));
    }
}
