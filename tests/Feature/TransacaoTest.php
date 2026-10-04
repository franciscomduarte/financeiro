<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FaseTransacao;
use App\Enums\FormaPagamento;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\TaxaCartao;
use App\Models\Transacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Seed taxas de cartão necessárias para os testes
        TaxaCartao::insert([
            ['id' => fake()->uuid(), 'tenant_id' => $this->clinica->id, 'modalidade' => 'pix', 'percentual' => 0.00, 'ativo' => true],
            ['id' => fake()->uuid(), 'tenant_id' => $this->clinica->id, 'modalidade' => 'dinheiro', 'percentual' => 0.00, 'ativo' => true],
            ['id' => fake()->uuid(), 'tenant_id' => $this->clinica->id, 'modalidade' => 'debito', 'percentual' => 1.50, 'ativo' => true],
            ['id' => fake()->uuid(), 'tenant_id' => $this->clinica->id, 'modalidade' => 'credito_1x', 'percentual' => 2.50, 'ativo' => true],
            ['id' => fake()->uuid(), 'tenant_id' => $this->clinica->id, 'modalidade' => 'credito_3x', 'percentual' => 3.50, 'ativo' => true],
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/transacoes
    // -------------------------------------------------------------------------

    public function test_criar_entrada_pix_calcula_imposto_sem_taxa(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transacoes', [
            'tipo'             => 'entrada',
            'fase'             => 'operacao',
            'categoria'        => 'Procedimento Facial',
            'descricao'        => 'Limpeza de pele',
            'valor_bruto'      => 200.00,
            'forma_pagamento'  => 'pix',
            'data_competencia' => '2026-03-25',
            'status'           => 'pago',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tipo', 'entrada')
            ->assertJsonPath('data.taxa_operacional', '0.00')
            ->assertJsonPath('data.imposto_estimado', '12.00')   // 200 * 0.06
            ->assertJsonPath('data.valor_liquido', '188.00');    // 200 - 0 - 12
    }

    public function test_criar_entrada_credito_aplica_taxa_e_imposto(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transacoes', [
            'tipo'             => 'entrada',
            'fase'             => 'operacao',
            'categoria'        => 'Depilação',
            'descricao'        => 'Depilação completa',
            'valor_bruto'      => 100.00,
            'forma_pagamento'  => 'credito_1x',
            'data_competencia' => '2026-03-25',
            'status'           => 'pago',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.taxa_operacional', '2.50')    // 100 * 2.5%
            ->assertJsonPath('data.imposto_estimado', '6.00')    // 100 * 6%
            ->assertJsonPath('data.valor_liquido', '91.50');     // 100 - 2.5 - 6
    }

    public function test_criar_saida_nao_aplica_imposto(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transacoes', [
            'tipo'             => 'saida',
            'fase'             => 'operacao',
            'categoria'        => 'Infraestrutura',
            'descricao'        => 'Aluguel',
            'valor_bruto'      => 1000.00,
            'forma_pagamento'  => 'pix',
            'data_competencia' => '2026-03-01',
            'status'           => 'pendente',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tipo', 'saida')
            ->assertJsonPath('data.imposto_estimado', '0.00')
            ->assertJsonPath('data.taxa_operacional', '0.00')
            ->assertJsonPath('data.valor_liquido', '1000.00');
    }

    public function test_criar_transacao_fase_implantacao(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transacoes', [
            'tipo'             => 'saida',
            'fase'             => 'implantacao',
            'categoria'        => 'Reforma',
            'descricao'        => 'Projeto arquitetônico',
            'valor_bruto'      => 3700.00,
            'forma_pagamento'  => 'pix',
            'data_competencia' => '2026-01-10',
            'status'           => 'pago',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.fase', 'implantacao');
    }

    public function test_criar_transacao_requer_autenticacao(): void
    {
        $this->postJson('/api/v1/transacoes', [])->assertStatus(401);
    }

    public function test_criar_transacao_valida_campos_obrigatorios(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/transacoes', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tipo', 'fase', 'categoria', 'descricao', 'valor_bruto', 'forma_pagamento', 'data_competencia']);
    }

    public function test_criar_transacao_rejeita_valores_negativos(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/transacoes', [
                'tipo'             => 'entrada',
                'fase'             => 'operacao',
                'categoria'        => 'Teste',
                'descricao'        => 'Teste',
                'valor_bruto'      => -100.00,
                'forma_pagamento'  => 'pix',
                'data_competencia' => '2026-03-25',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['valor_bruto']);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/transacoes
    // -------------------------------------------------------------------------

    public function test_listar_transacoes_com_paginacao(): void
    {
        Transacao::factory()->count(25)->create([
            'tipo'             => TipoTransacao::Entrada,
            'fase'             => FaseTransacao::Operacao,
            'forma_pagamento'  => FormaPagamento::Pix,
            'status'           => StatusTransacao::Pago,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/transacoes');

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 25);
    }

    public function test_filtrar_transacoes_por_tipo(): void
    {
        Transacao::factory()->count(3)->create(['tipo' => TipoTransacao::Entrada, 'forma_pagamento' => FormaPagamento::Pix, 'status' => StatusTransacao::Pago]);
        Transacao::factory()->count(2)->create(['tipo' => TipoTransacao::Saida, 'forma_pagamento' => FormaPagamento::Pix, 'status' => StatusTransacao::Pago]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/transacoes?tipo=entrada')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_filtrar_transacoes_por_fase(): void
    {
        Transacao::factory()->count(2)->create(['fase' => FaseTransacao::Implantacao, 'tipo' => TipoTransacao::Saida, 'forma_pagamento' => FormaPagamento::Pix, 'status' => StatusTransacao::Pago]);
        Transacao::factory()->count(4)->create(['fase' => FaseTransacao::Operacao, 'tipo' => TipoTransacao::Entrada, 'forma_pagamento' => FormaPagamento::Pix, 'status' => StatusTransacao::Pago]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/transacoes?fase=implantacao')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filtrar_transacoes_por_periodo(): void
    {
        Transacao::factory()->create(['data_competencia' => '2026-02-15', 'tipo' => TipoTransacao::Entrada, 'forma_pagamento' => FormaPagamento::Pix, 'status' => StatusTransacao::Pago]);
        Transacao::factory()->create(['data_competencia' => '2026-03-10', 'tipo' => TipoTransacao::Entrada, 'forma_pagamento' => FormaPagamento::Pix, 'status' => StatusTransacao::Pago]);
        Transacao::factory()->create(['data_competencia' => '2026-03-25', 'tipo' => TipoTransacao::Entrada, 'forma_pagamento' => FormaPagamento::Pix, 'status' => StatusTransacao::Pago]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/transacoes?periodo[inicio]=2026-03-01&periodo[fim]=2026-03-31')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/transacoes/{id}
    // -------------------------------------------------------------------------

    public function test_exibir_transacao_com_anexos(): void
    {
        $transacao = Transacao::factory()->create([
            'tipo'            => TipoTransacao::Entrada,
            'forma_pagamento' => FormaPagamento::Pix,
            'status'          => StatusTransacao::Pago,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/transacoes/{$transacao->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $transacao->id)
            ->assertJsonStructure(['data' => ['id', 'tipo', 'fase', 'valor_bruto', 'valor_liquido', 'anexos']]);
    }

    public function test_exibir_transacao_inexistente_retorna_404(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/transacoes/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/transacoes/{id}
    // -------------------------------------------------------------------------

    public function test_atualizar_transacao_recalcula_valores(): void
    {
        $transacao = Transacao::factory()->create([
            'tipo'             => TipoTransacao::Entrada,
            'forma_pagamento'  => FormaPagamento::Pix,
            'valor_bruto'      => 100.00,
            'taxa_operacional' => 0.00,
            'imposto_estimado' => 6.00,
            'valor_liquido'    => 94.00,
            'status'           => StatusTransacao::Pendente,
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/transacoes/{$transacao->id}", [
                'valor_bruto'     => 200.00,
                'forma_pagamento' => 'debito',
            ])
            ->assertOk()
            ->assertJsonPath('data.taxa_operacional', '3.00')    // 200 * 1.5%
            ->assertJsonPath('data.imposto_estimado', '12.00')   // 200 * 6%
            ->assertJsonPath('data.valor_liquido', '185.00');    // 200 - 3 - 12
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/transacoes/{id}
    // -------------------------------------------------------------------------

    public function test_cancelar_transacao_muda_status(): void
    {
        $transacao = Transacao::factory()->create([
            'tipo'            => TipoTransacao::Saida,
            'forma_pagamento' => FormaPagamento::Pix,
            'status'          => StatusTransacao::Pendente,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/transacoes/{$transacao->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Transação cancelada com sucesso.');

        $this->assertDatabaseHas('transacoes', [
            'id'     => $transacao->id,
            'status' => StatusTransacao::Cancelado->value,
        ]);
    }
}
