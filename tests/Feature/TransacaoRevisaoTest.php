<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CreateTransacaoAction;
use App\Enums\StatusTransacao;
use App\Livewire\TransacaoIndex;
use App\Models\TaxaCartao;
use App\Models\Transacao;
use App\Models\TransacaoAnexo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Correções da revisão do módulo de transações. */
class TransacaoRevisaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        TaxaCartao::insert([
            ['id' => fake()->uuid(), 'tenant_id' => $this->clinica->id, 'modalidade' => 'credito_3x', 'percentual' => 3.50, 'ativo' => true],
        ]);
    }

    private function criar(array $dados = []): Transacao
    {
        return app(CreateTransacaoAction::class)->execute(array_merge([
            'tipo'             => 'entrada',
            'fase'             => 'operacao',
            'categoria'        => 'Massagem',
            'descricao'        => 'Teste',
            'valor_bruto'      => 100,
            'forma_pagamento'  => 'pix',
            'data_competencia' => '2026-10-01',
            'status'           => 'pendente',
        ], $dados));
    }

    public function test_saida_no_cartao_nao_desconta_taxa_de_maquininha(): void
    {
        $t = $this->criar(['tipo' => 'saida', 'categoria' => 'Insumos', 'forma_pagamento' => 'credito_3x']);

        $this->assertSame('0.00', $t->taxa_operacional);
        $this->assertSame('100.00', $t->valor_liquido);
    }

    public function test_entrada_no_cartao_continua_com_taxa(): void
    {
        $t = $this->criar(['forma_pagamento' => 'credito_3x']);

        $this->assertSame('3.50', $t->taxa_operacional);
    }

    public function test_pago_sem_data_recebe_data_de_hoje(): void
    {
        $t = $this->criar(['status' => 'pago']);

        $this->assertSame(now()->toDateString(), $t->data_pagamento->toDateString());
    }

    public function test_tela_exige_data_quando_pago_e_preenche_ao_escolher_pago(): void
    {
        Livewire::test(TransacaoIndex::class)
            ->call('abrirModalCriar')
            ->set('status', 'pago')
            ->assertSet('dataPagamento', now()->toDateString())
            ->set('dataPagamento', '')
            ->set('categoria', 'Massagem')
            ->set('descricao', 'Teste')
            ->set('valorBruto', '100')
            ->call('salvarNova')
            ->assertHasErrors(['dataPagamento' => 'required_if']);
    }

    public function test_parcelas_vem_da_forma_de_pagamento(): void
    {
        $this->assertSame(3, $this->criar(['forma_pagamento' => 'credito_3x'])->num_parcelas);
        $this->assertSame(1, $this->criar(['forma_pagamento' => 'pix'])->num_parcelas);
    }

    public function test_editar_pela_tela_nao_apaga_cliente(): void
    {
        $t = $this->criar(['cliente' => 'Maria Souza']);

        Livewire::test(TransacaoIndex::class)
            ->call('abrirModalEditar', $t->id)
            ->set('descricao', 'Alterada')
            ->call('salvarEdicao')
            ->assertHasNoErrors();

        $this->assertSame('Maria Souza', $t->fresh()->cliente);
        $this->assertSame('Alterada', $t->fresh()->descricao);
    }

    public function test_totais_respeitam_filtro_de_status(): void
    {
        $this->criar(['valor_bruto' => 100, 'status' => 'pago']);
        $this->criar(['valor_bruto' => 50, 'status' => 'pendente']);

        Livewire::test(TransacaoIndex::class)
            ->assertViewHas('totalEntradas', 150.0)
            ->set('filtroStatus', 'pendente')
            ->assertViewHas('totalEntradas', 50.0);
    }

    public function test_marcar_como_pago(): void
    {
        $t = $this->criar();

        Livewire::test(TransacaoIndex::class)->call('marcarComoPago', $t->id);

        $this->assertSame(StatusTransacao::Pago, $t->fresh()->status);
        $this->assertSame(now()->toDateString(), $t->fresh()->data_pagamento->toDateString());
    }

    public function test_remover_anexo_apaga_o_arquivo(): void
    {
        Storage::fake('local');
        $t     = $this->criar();
        $anexo = TransacaoAnexo::factory()->create(['transacao_id' => $t->id, 'caminho' => 'anexos/x.pdf']);
        Storage::disk('local')->put('anexos/x.pdf', 'pdf');

        Livewire::test(TransacaoIndex::class)
            ->call('abrirModalDetalhe', $t->id)
            ->call('removerAnexo', $anexo->id);

        Storage::disk('local')->assertMissing('anexos/x.pdf');
        $this->assertModelMissing($anexo);
    }

    public function test_preencher_por_voz_recalcula_valores(): void
    {
        Livewire::test(TransacaoIndex::class)
            ->call('abrirModalCriar')
            ->call('preencherVoz', [
                'tipo' => 'entrada', 'categoria' => 'Massagem', 'descricao' => 'Massagem',
                'valor_bruto' => 200, 'forma_pagamento' => 'pix', 'status' => 'pago',
                'data_competencia' => '2026-10-01',
            ])
            ->assertSet('impostoEstimado', 12.0)
            ->assertSet('valorLiquido', 188.0)
            ->assertSet('dataPagamento', now()->toDateString());
    }

    public function test_api_rejeita_paciente_inexistente(): void
    {
        $this->postJson('/api/v1/transacoes', [
            'tipo' => 'entrada', 'fase' => 'operacao', 'categoria' => 'Massagem', 'descricao' => 'X',
            'valor_bruto' => 10, 'forma_pagamento' => 'pix', 'data_competencia' => '2026-10-01',
            'paciente_id' => fake()->uuid(),
        ])->assertStatus(422)->assertJsonValidationErrors(['paciente_id']);
    }
}
