<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contrato;
use App\Models\ContratoReajuste;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RegistrarReajusteAction
{
    public function execute(Contrato $contrato, array $data): ContratoReajuste
    {
        return DB::transaction(function () use ($contrato, $data): ContratoReajuste {
            $valorAnterior = (float) $contrato->getRawOriginal('valor_mensal');
            $valorNovo     = (float) $data['valor_novo'];

            $percentualEfetivo = $valorAnterior > 0
                ? round((($valorNovo - $valorAnterior) / $valorAnterior) * 100, 2)
                : null;

            $reajuste = ContratoReajuste::create([
                'contrato_id'        => $contrato->id,
                'data_reajuste'      => $data['data_reajuste'],
                'valor_anterior'     => $valorAnterior,
                'valor_novo'         => $valorNovo,
                'indice'             => $data['indice'] ?? null,
                'percentual_efetivo' => $percentualEfetivo,
                'observacoes'        => $data['observacoes'] ?? null,
                'created_at'         => now(),
            ]);

            // Calcular próxima data de reajuste
            $dataReajuste       = Carbon::parse($data['data_reajuste']);
            $periodicidade      = $contrato->getRawOriginal('periodicidade_reajuste');
            $proximaData        = match ($periodicidade) {
                'semestral' => $dataReajuste->copy()->addMonths(6),
                default     => $dataReajuste->copy()->addYear(),
            };

            $contrato->update([
                'valor_mensal'          => $valorNovo,
                'data_proximo_reajuste' => $proximaData->toDateString(),
            ]);

            return $reajuste;
        });
    }
}
