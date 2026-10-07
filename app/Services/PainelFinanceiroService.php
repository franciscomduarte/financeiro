<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusTransacao;
use App\Models\Transacao;
use Carbon\CarbonImmutable;

/** Números do financeiro para o painel inicial: saldo, o que vence na semana, atrasados e resultado do mês. */
class PainelFinanceiroService
{
    public function __construct(
        private readonly FluxoCaixaService $fluxo,
        private readonly DreService $dre,
    ) {}

    /**
     * @return array{saldo: float, receber_semana: float, pagar_semana: float, receber_vencido: float, pagar_vencido: float,
     *               resultado_mes: float, faturamento_mes: float, projecao: array<int, array{data: CarbonImmutable, saldo: float}>,
     *               saldo_30: float, menor_saldo: float, menor_saldo_em: ?CarbonImmutable}
     */
    public function resumo(): array
    {
        $hoje   = today()->toDateString();
        $semana = today()->addDays(7)->toDateString();
        $aberto = 'GREATEST(valor_bruto - valor_pago, 0)';

        $r = Transacao::query()
            ->whereIn('status', StatusTransacao::abertos())
            ->where('data_vencimento', '<=', $semana)
            ->selectRaw("
                COALESCE(SUM({$aberto}) FILTER (WHERE tipo = 'entrada' AND data_vencimento >= ?), 0) AS receber_semana,
                COALESCE(SUM({$aberto}) FILTER (WHERE tipo = 'saida' AND data_vencimento >= ?), 0) AS pagar_semana,
                COALESCE(SUM({$aberto}) FILTER (WHERE tipo = 'entrada' AND data_vencimento < ?), 0) AS receber_vencido,
                COALESCE(SUM({$aberto}) FILTER (WHERE tipo = 'saida' AND data_vencimento < ?), 0) AS pagar_vencido
            ", [$hoje, $hoje, $hoje, $hoje])
            ->toBase()
            ->first();

        $projecao = $this->fluxo->projetado(30);
        $dre      = $this->dre->mes(CarbonImmutable::today()->startOfMonth());

        return [
            'saldo'           => $projecao['saldo_hoje'],
            'receber_semana'  => round((float) $r->receber_semana, 2),
            'pagar_semana'    => round((float) $r->pagar_semana, 2),
            'receber_vencido' => round((float) $r->receber_vencido, 2),
            'pagar_vencido'   => round((float) $r->pagar_vencido, 2),
            'resultado_mes'   => $dre['resultado'],
            'faturamento_mes' => $dre['receita_bruta'],
            'projecao'        => $projecao['diario'],
            'saldo_30'        => $projecao['saldo_final'],
            'menor_saldo'     => $projecao['menor_saldo'],
            'menor_saldo_em'  => $projecao['menor_saldo_em'],
        ];
    }
}
