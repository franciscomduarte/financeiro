<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FaseTransacao;
use App\Enums\StatusFatura;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\ContaConsumoFatura;
use Illuminate\Support\Facades\DB;

class PagarFaturaAction
{
    public function __construct(
        private readonly CreateTransacaoAction $createTransacao,
    ) {}

    public function execute(ContaConsumoFatura $fatura, array $data): ContaConsumoFatura
    {
        return DB::transaction(function () use ($fatura, $data): ContaConsumoFatura {
            $conta = $fatura->contaConsumo()->with('fornecedor')->firstOrFail();

            $valor        = !empty($data['valor']) ? (float) $data['valor'] : (float) $fatura->valor;
            $dataPagamento = $data['data_pagamento'] ?? now()->toDateString();

            [$ano, $mes] = explode('-', $fatura->competencia);
            $dataCompetencia = sprintf('%s-%s-01', $ano, $mes);

            $transacao = $this->createTransacao->execute([
                'tipo'             => TipoTransacao::Saida->value,
                'fase'             => FaseTransacao::Operacao->value,
                'categoria'        => $conta->tipo->categoria(),
                'subcategoria'     => $conta->tipo->label(),
                'descricao'        => $conta->descricao . ' — ' . $fatura->competenciaFormatada(),
                'fornecedor_id'    => $conta->fornecedor_id,
                'valor_bruto'      => $valor,
                'forma_pagamento'  => $data['forma_pagamento'],
                'data_competencia' => $dataCompetencia,
                'data_pagamento'   => $dataPagamento,
                'status'           => StatusTransacao::Pago->value,
                'observacoes'      => 'Fatura de consumo lançada automaticamente.',
            ]);

            $fatura->update([
                'status'         => StatusFatura::Paga,
                'valor'          => $valor,
                'data_pagamento' => $dataPagamento,
                'transacao_id'   => $transacao->id,
            ]);

            return $fatura->fresh();
        });
    }
}
