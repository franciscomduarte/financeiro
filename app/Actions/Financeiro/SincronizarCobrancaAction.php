<?php

declare(strict_types=1);

namespace App\Actions\Financeiro;

use App\Actions\CreateTransacaoAction;
use App\Actions\UpdateTransacaoAction;
use App\Enums\FormaPagamento;
use App\Enums\StatusTransacao;
use App\Models\Cobranca;
use App\Models\ContaFinanceira;
use App\Models\Transacao;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cobrança do Asaas no financeiro: em aberto vira conta a receber; paga dá baixa na conta bancária
 * padrão; excluída cancela; estornada desfaz o recebimento e cancela.
 */
class SincronizarCobrancaAction
{
    public function __construct(
        private readonly CreateTransacaoAction $criar,
        private readonly UpdateTransacaoAction $atualizar,
        private readonly BaixarTransacaoAction $baixar,
    ) {}

    public function execute(Cobranca $cobranca): void
    {
        DB::transaction(function () use ($cobranca): void {
            $t = $cobranca->transacao_id ? Transacao::query()->find($cobranca->transacao_id) : null;

            if ($t === null) {
                if (! in_array($cobranca->status, ['PENDING', 'OVERDUE', 'RECEIVED'], true)) {
                    return;
                }
                $t = $this->criarTitulo($cobranca);
                $cobranca->forceFill(['transacao_id' => $t->id])->saveQuietly();
            }

            match ($cobranca->status) {
                'RECEIVED' => $this->receber($cobranca, $t),
                'DELETED'  => $t->status->emAberto() && (float) $t->valor_pago <= 0
                    ? $this->atualizar->execute($t, ['status' => StatusTransacao::Cancelado->value]) : null,
                'REFUNDED' => $t->status !== StatusTransacao::Cancelado
                    ? $this->atualizar->execute($t, ['status' => StatusTransacao::Cancelado->value]) : null,
                default    => null,
            };
        });
    }

    private function criarTitulo(Cobranca $c): Transacao
    {
        $c->loadMissing(['paciente:id,nome', 'parcelamento:id,descricao,total_parcelas']);
        $vencimento = CarbonImmutable::parse($c->vencimento);
        $parcela    = $c->parcelamento_id !== null;

        return $this->criar->execute([
            'tipo' => 'entrada', 'fase' => 'operacao',
            'categoria' => $parcela ? 'Outros' : 'Mensalidades',
            'descricao' => mb_substr($parcela
                ? ($c->parcelamento?->descricao ?: 'Parcelamento') . " — parcela {$c->numero_parcela}/" . ($c->parcelamento?->total_parcelas ?? '?')
                : 'Mensalidade ' . ($c->paciente?->nome ?? '') . ' — ' . $c->mes_referencia, 0, 255),
            'paciente_id' => $c->paciente_id,
            'valor_bruto' => (float) $c->valor, 'forma_pagamento' => FormaPagamento::Pix->value,
            'data_competencia' => $parcela ? $vencimento->toDateString() : $c->mes_referencia . '-01',
            'data_vencimento' => $vencimento->toDateString(),
            'status' => 'pendente', 'observacoes' => "Cobrança Asaas {$c->asaas_id}",
        ]);
    }

    private function receber(Cobranca $c, Transacao $t): void
    {
        $t->refresh();
        if (! $t->status->emAberto()) {
            return;
        }
        $conta = ContaFinanceira::sugeridaPara(FormaPagamento::Pix);
        if ($conta === null) {
            Log::warning('[Asaas] sem conta para registrar o recebimento', ['tenant_id' => $t->tenant_id, 'cobranca' => $c->id]);

            return;
        }
        $this->baixar->execute($t, [
            'valor' => $t->valorAberto(), 'data' => ($c->pago_em ? CarbonImmutable::parse($c->pago_em) : CarbonImmutable::today())->toDateString(),
            'conta_financeira_id' => $conta->id, 'forma_pagamento' => FormaPagamento::Pix->value, 'observacoes' => 'Pago pelo Asaas',
        ]);
    }
}
