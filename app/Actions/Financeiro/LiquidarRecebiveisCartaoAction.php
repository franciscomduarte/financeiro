<?php

declare(strict_types=1);

namespace App\Actions\Financeiro;

use App\Actions\CreateTransacaoAction;
use App\Models\RecebivelCartao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Libera os recebíveis de cartão que venceram: transfere da Maquininha para a conta de liquidação
 * (a conta bancária padrão, se nenhuma for escolhida) e lança a taxa de antecipação como despesa
 * financeira. Roda todo dia para a clínica ativa.
 */
class LiquidarRecebiveisCartaoAction
{
    public function __construct(
        private readonly TransferirEntreContasAction $transferir,
        private readonly CreateTransacaoAction $criarLancamento,
    ) {}

    public function execute(): int
    {
        $liquidados = 0;

        RecebivelCartao::query()
            ->pendentes()
            ->where('data_prevista', '<=', today()->toDateString())
            ->with(['maquininha', 'transacao:id,descricao,data_competencia'])
            ->orderBy('data_prevista')
            ->limit(500)
            ->get()
            ->each(function (RecebivelCartao $r) use (&$liquidados): void {
                try {
                    DB::transaction(function () use ($r): void {
                        $r = RecebivelCartao::query()->lockForUpdate()->findOrFail($r->id);
                        if ($r->liquidado_em !== null) {
                            return;
                        }
                        $maquininha = $r->maquininha()->firstOrFail();
                        $descricao  = $r->transacao?->descricao ?? 'venda no cartão';
                        $dados      = ['liquidado_em' => $r->data_prevista];

                        if ((float) $r->taxa_antecipacao > 0) {
                            $dados['despesa_id'] = $this->criarLancamento->execute([
                                'tipo' => 'saida', 'fase' => 'operacao', 'categoria' => 'Taxa de antecipação',
                                'descricao' => mb_substr("Antecipação do cartão · {$descricao}", 0, 255),
                                'valor_bruto' => (float) $r->taxa_antecipacao, 'forma_pagamento' => 'pix',
                                'data_competencia' => $r->transacao?->data_competencia?->toDateString() ?? $r->data_prevista->toDateString(),
                                'data_pagamento' => $r->data_prevista->toDateString(), 'status' => 'pago',
                                'conta_financeira_id' => $maquininha->id,
                            ])->id;
                        }

                        $destino = $maquininha->contaLiquidacao();
                        if ($destino !== null && (float) $r->valor_liquido > 0) {
                            $dados['transferencia_id'] = $this->transferir->execute([
                                'conta_origem_id' => $maquininha->id, 'conta_destino_id' => $destino->id,
                                'data' => $r->data_prevista->toDateString(), 'valor' => (float) $r->valor_liquido,
                                'descricao' => mb_substr(($r->antecipado ? 'Antecipação' : "Parcela {$r->parcela}/{$r->total_parcelas}") . " · {$descricao}", 0, 255),
                            ])->id;
                        }

                        $r->forceFill($dados)->save();
                    });
                    $liquidados++;
                } catch (Throwable $e) {
                    Log::error('[Financeiro] falha ao liberar recebível de cartão', ['tenant_id' => $r->tenant_id, 'recebivel' => $r->id, 'erro' => $e->getMessage()]);
                }
            });

        return $liquidados;
    }
}
