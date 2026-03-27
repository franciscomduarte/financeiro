<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\Fornecedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContratoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Fornecedor $fornecedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user       = User::factory()->create();
        $this->fornecedor = Fornecedor::factory()->create();
    }

    // ─── POST /api/v1/contratos ──────────────────────────────────

    public function test_criar_contrato_minimo(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/contratos', [
            'fornecedor_id' => $this->fornecedor->id,
            'valor_mensal'  => 1500.00,
            'data_inicio'   => '2026-01-01',
            'risco'         => 'baixo',
            'status'        => 'ativo',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.risco', 'baixo')
            ->assertJsonPath('data.status', 'ativo');

        $this->assertDatabaseHas('contratos', ['valor_mensal' => 1500.00]);
    }

    public function test_criar_contrato_com_fornecedor(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/contratos', [
            'fornecedor_id' => $this->fornecedor->id,
            'valor_mensal'  => 3200.00,
            'data_inicio'   => '2026-01-01',
            'data_fim'      => '2026-12-31',
            'risco'         => 'medio',
            'status'        => 'ativo',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.fornecedor_id', $this->fornecedor->id)
            ->assertJsonPath('data.risco', 'medio');
    }

    public function test_criar_contrato_com_dados_de_reajuste(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/contratos', [
            'fornecedor_id'          => $this->fornecedor->id,
            'valor_mensal'           => 2000.00,
            'data_inicio'            => '2026-01-01',
            'periodicidade_reajuste' => 'anual',
            'indice_reajuste'        => 'ipca',
            'data_proximo_reajuste'  => '2027-01-01',
            'risco'                  => 'baixo',
            'status'                 => 'ativo',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.periodicidade_reajuste', 'anual')
            ->assertJsonPath('data.indice_reajuste', 'ipca');
    }

    public function test_criar_contrato_falha_sem_valor_mensal(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/contratos', [
            'risco'  => 'baixo',
            'status' => 'ativo',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['valor_mensal']);
    }

    public function test_criar_contrato_falha_com_risco_invalido(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/contratos', [
            'valor_mensal' => 1000.00,
            'risco'        => 'extremo',
            'status'       => 'ativo',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['risco']);
    }

    public function test_criar_contrato_falha_com_fornecedor_inexistente(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/contratos', [
            'fornecedor_id' => 'uuid-que-nao-existe',
            'valor_mensal'  => 1000.00,
            'risco'         => 'baixo',
            'status'        => 'ativo',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fornecedor_id']);
    }

    public function test_criar_contrato_falha_com_link_invalido(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/contratos', [
            'fornecedor_id' => $this->fornecedor->id,
            'valor_mensal'  => 1000.00,
            'data_inicio'   => '2026-01-01',
            'link_contrato' => 'nao-eh-url',
            'risco'         => 'baixo',
            'status'        => 'ativo',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['link_contrato']);
    }

    // ─── GET /api/v1/contratos ───────────────────────────────────

    public function test_listar_contratos_retorna_paginado(): void
    {
        Contrato::factory()->count(5)->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/contratos');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'fornecedor_id', 'valor_mensal', 'status', 'risco']],
                'meta' => ['total'],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_listar_contratos_filtra_por_status(): void
    {
        Contrato::factory()->count(3)->ativo()->create();
        Contrato::factory()->count(2)->encerrado()->create();

        $response = $this->actingAs($this->user)->getJson('/api/v1/contratos?status=ativo');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
        foreach ($response->json('data') as $item) {
            $this->assertEquals('ativo', $item['status']);
        }
    }

    public function test_listar_contratos_filtra_alertas(): void
    {
        // Contrato que vence em 30 dias (alerta)
        Contrato::factory()->create([
            'data_fim' => now()->addDays(30)->toDateString(),
            'status'   => 'ativo',
        ]);
        // Contrato sem vencimento próximo
        Contrato::factory()->create([
            'data_fim' => now()->addDays(90)->toDateString(),
            'status'   => 'ativo',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/contratos?alertas=true');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // ─── GET /api/v1/contratos/{id} ──────────────────────────────

    public function test_mostrar_contrato_inclui_fornecedor_e_reajustes(): void
    {
        $contrato = Contrato::factory()->create(['fornecedor_id' => $this->fornecedor->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/contratos/{$contrato->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $contrato->id)
            ->assertJsonStructure(['data' => ['fornecedor', 'reajustes']]);
    }

    // ─── PUT /api/v1/contratos/{id} ──────────────────────────────

    public function test_atualizar_contrato(): void
    {
        $contrato = Contrato::factory()->create(['status' => 'ativo', 'risco' => 'baixo']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/contratos/{$contrato->id}", [
            'valor_mensal' => 9999.00,
            'risco'        => 'alto',
            'status'       => 'suspenso',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.risco', 'alto')
            ->assertJsonPath('data.status', 'suspenso');
    }

    // ─── POST /api/v1/contratos/{id}/reajuste ────────────────────

    public function test_registrar_reajuste_atualiza_valor_e_cria_historico(): void
    {
        $contrato = Contrato::factory()->create([
            'valor_mensal'           => 1000.00,
            'periodicidade_reajuste' => 'anual',
            'status'                 => 'ativo',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/contratos/{$contrato->id}/reajuste", [
            'data_reajuste' => '2026-01-01',
            'valor_novo'    => 1100.00,
            'indice'        => 'ipca',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.valor_anterior', '1000.00')
            ->assertJsonPath('data.valor_novo', '1100.00')
            ->assertJsonPath('data.percentual_efetivo', '10.00');

        $this->assertDatabaseHas('contratos', [
            'id'           => $contrato->id,
            'valor_mensal' => 1100.00,
        ]);

        $this->assertDatabaseHas('contratos_reajustes', [
            'contrato_id'        => $contrato->id,
            'valor_anterior'     => 1000.00,
            'valor_novo'         => 1100.00,
            'percentual_efetivo' => 10.00,
        ]);
    }

    public function test_registrar_reajuste_falha_sem_valor_novo(): void
    {
        $contrato = Contrato::factory()->create();

        $response = $this->actingAs($this->user)->postJson("/api/v1/contratos/{$contrato->id}/reajuste", [
            'data_reajuste' => '2026-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['valor_novo']);
    }

    // ─── Auth ────────────────────────────────────────────────────

    public function test_rotas_requerem_autenticacao(): void
    {
        $this->getJson('/api/v1/contratos')->assertStatus(401);
        $this->postJson('/api/v1/contratos', [])->assertStatus(401);
    }
}
