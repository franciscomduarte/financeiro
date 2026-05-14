<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FaseTransacao;
use App\Enums\StatusLancamentoFiscal;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\ObrigacaoFiscalLancamento;
use Illuminate\Support\Facades\DB;

class PagarGuiaFiscalAction
{
    public function __construct(
        private readonly CreateTransacaoAction $createTransacao,
    ) {}

    public function execute(ObrigacaoFiscalLancamento $lancamento, array $data): ObrigacaoFiscalLancamento
    {
        return DB::transaction(function () use ($lancamento, $data): ObrigacaoFiscalLancamento {
            $obrigacao     = $lancamento->obrigacaoFiscal()->firstOrFail();
            $dataPagamento = $data['data_pagamento'] ?? now()->toDateString();
            $valorTotal    = $lancamento->valorTotal();

            // Se houve multa/juros adicionados no pagamento, atualiza o lancamento antes
            if (isset($data['valor_multa']) || isset($data['valor_juros'])) {
                $lancamento->update([
                    'valor_multa'  => isset($data['valor_multa']) ? (float) $data['valor_multa'] : $lancamento->valor_multa,
                    'valor_juros'  => isset($data['valor_juros']) ? (float) $data['valor_juros'] : $lancamento->valor_juros,
                ]);
                $lancamento->refresh();
                $valorTotal = $lancamento->valorTotal();
            }

            [$ano, $mes] = explode('-', $lancamento->competencia);
            $dataCompetencia = sprintf('%s-%s-01', $ano, $mes);

            $transacao = $this->createTransacao->execute([
                'tipo'             => TipoTransacao::Saida->value,
                'fase'             => FaseTransacao::Operacao->value,
                'categoria'        => $obrigacao->tipo_tributo->categoria(),
                'subcategoria'     => $obrigacao->tipo_tributo->label(),
                'descricao'        => $obrigacao->descricao . ' — ' . $lancamento->competenciaFormatada(),
                'valor_bruto'      => $valorTotal,
                'forma_pagamento'  => $data['forma_pagamento'],
                'data_competencia' => $dataCompetencia,
                'data_pagamento'   => $dataPagamento,
                'status'           => StatusTransacao::Pago->value,
                'observacoes'      => $lancamento->temMultaOuJuros()
                    ? sprintf('Multa: R$ %.2f | Juros: R$ %.2f', $lancamento->valor_multa, $lancamento->valor_juros)
                    : null,
            ]);

            $lancamento->update([
                'status'              => StatusLancamentoFiscal::Pago,
                'data_pagamento'      => $dataPagamento,
                'numero_autenticacao' => $data['numero_autenticacao'] ?? null,
                'transacao_id'        => $transacao->id,
            ]);

            return $lancamento->fresh();
        });
    }
}
