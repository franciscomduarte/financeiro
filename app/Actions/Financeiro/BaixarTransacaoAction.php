<?php

declare(strict_types=1);

namespace App\Actions\Financeiro;

use App\Enums\FormaPagamento;
use App\Enums\TipoTransacao;
use App\Models\ContaFinanceira;
use App\Models\Transacao;
use App\Models\TransacaoBaixa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Registra o pagamento (conta a pagar) ou recebimento (conta a receber) de um lançamento, total ou
 * parcial, com juros, multa e desconto. Atualiza o valor pago e a situação do lançamento.
 */
class BaixarTransacaoAction
{
    /**
     * @param  array{valor: float|string, data: string, conta_financeira_id: string, juros?: float|string|null,
     *               multa?: float|string|null, desconto?: float|string|null, forma_pagamento?: ?string, observacoes?: ?string, antecipar?: ?bool}  $dados
     */
    public function execute(Transacao $transacao, array $dados): TransacaoBaixa
    {
        return DB::transaction(function () use ($transacao, $dados): TransacaoBaixa {
            /** @var Transacao $t */
            $t = Transacao::query()->lockForUpdate()->findOrFail($transacao->id);

            if (! $t->status->emAberto()) {
                throw new RuntimeException('Este lançamento já está quitado ou cancelado.');
            }

            $aberto   = $t->valorAberto();
            $valor    = round((float) $dados['valor'], 2);
            $juros    = round((float) ($dados['juros'] ?? 0), 2);
            $multa    = round((float) ($dados['multa'] ?? 0), 2);
            $desconto = round((float) ($dados['desconto'] ?? 0), 2);

            if ($valor <= 0 || $valor > $aberto + 0.004) {
                throw new RuntimeException('O valor precisa ser maior que zero e no máximo R$ ' . number_format($aberto, 2, ',', '.') . ' (o que falta).');
            }

            $conta = ContaFinanceira::query()->ativas()->select(['id'])->findOrFail($dados['conta_financeira_id']);
            $forma = FormaPagamento::tryFrom((string) ($dados['forma_pagamento'] ?? '')) ?? $t->forma_pagamento;
            $taxa  = self::taxaProporcional($t, $valor);

            $movimentado = round($valor + $juros + $multa - $desconto - $taxa, 2);
            if ($movimentado < 0) {
                throw new RuntimeException('O desconto não pode ser maior que o valor pago.');
            }

            $baixa = TransacaoBaixa::query()->create([
                'transacao_id'        => $t->id,
                'conta_financeira_id' => $conta->id,
                'tipo'                => $t->tipo,
                'data'                => $dados['data'],
                'valor'               => $valor,
                'juros'               => $juros,
                'multa'               => $multa,
                'desconto'            => $desconto,
                'taxa'                => $taxa,
                'valor_movimentado'   => $movimentado,
                'forma_pagamento'     => $forma,
                'observacoes'         => $dados['observacoes'] ?? null,
                'user_id'             => auth()->id(),
            ]);

            app(GerarRecebiveisCartaoAction::class)->execute($baixa, isset($dados['antecipar']) ? (bool) $dados['antecipar'] : null);
            $t->recalcularPagamento();

            Log::info('[Financeiro] baixa registrada', [
                'tenant_id' => $t->tenant_id, 'user_id' => auth()->id(), 'transacao_id' => $t->id,
                'valor' => $valor, 'status' => $t->status->value,
            ]);

            return $baixa;
        });
    }

    /** Taxa da maquininha (só entradas) na proporção do valor recebido. */
    public static function taxaProporcional(Transacao $t, float $valor): float
    {
        $bruto = (float) $t->valor_bruto;
        if ($t->tipo !== TipoTransacao::Entrada || $bruto <= 0 || (float) $t->taxa_operacional <= 0) {
            return 0.0;
        }

        return round((float) $t->taxa_operacional * $valor / $bruto, 2);
    }
}
