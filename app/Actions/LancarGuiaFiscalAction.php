<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StatusLancamentoFiscal;
use App\Models\ObrigacaoFiscal;
use App\Models\ObrigacaoFiscalLancamento;
use Illuminate\Support\Facades\DB;

class LancarGuiaFiscalAction
{
    public function execute(ObrigacaoFiscal $obrigacao, array $data): ObrigacaoFiscalLancamento
    {
        return DB::transaction(function () use ($obrigacao, $data): ObrigacaoFiscalLancamento {
            $guia = ObrigacaoFiscalLancamento::create([
                'obrigacao_fiscal_id' => $obrigacao->id,
                'competencia'         => $data['competencia'],
                'data_vencimento'     => $data['data_vencimento'],
                'valor_principal'     => (float) $data['valor_principal'],
                'valor_multa'         => isset($data['valor_multa']) ? (float) $data['valor_multa'] : 0,
                'valor_juros'         => isset($data['valor_juros']) ? (float) $data['valor_juros'] : 0,
                'codigo_barras'       => $data['codigo_barras'] ?? null,
                'status'              => StatusLancamentoFiscal::Pendente,
                'observacoes'         => $data['observacoes'] ?? null,
            ]);
            app(\App\Actions\Financeiro\TitulosContasFixasAction::class)->guia($guia);

            return $guia->fresh();
        });
    }
}
