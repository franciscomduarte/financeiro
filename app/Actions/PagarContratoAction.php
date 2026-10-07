<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FaseTransacao;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\Contrato;
use App\Models\ContratoPagamento;
use Illuminate\Support\Facades\DB;

class PagarContratoAction
{
    public function __construct(
        private readonly CreateTransacaoAction $createTransacao,
    ) {}

    public function execute(Contrato $contrato, array $data): ContratoPagamento
    {
        return DB::transaction(function () use ($contrato, $data): ContratoPagamento {
            $contrato->load('fornecedor');

            $valor         = !empty($data['valor']) ? (float) $data['valor'] : (float) $contrato->getRawOriginal('valor_mensal');
            $dataPagamento = $data['data_pagamento'] ?? now()->toDateString();
            $competencia   = $data['competencia'];

            [$ano, $mes]     = explode('-', $competencia);
            $dataCompetencia = sprintf('%s-%s-01', $ano, $mes);

            $fornecedorNome = $contrato->fornecedor?->nome_fantasia ?? 'Fornecedor';

            [$inicioMes, $fimMes] = [$dataCompetencia, \Carbon\CarbonImmutable::parse($dataCompetencia)->endOfMonth()->toDateString()];
            $titulo = \App\Models\Transacao::query()->where('contrato_id', $contrato->id)
                ->whereBetween('data_competencia', [$inicioMes, $fimMes])
                ->whereIn('status', StatusTransacao::abertos())->first();

            if ($titulo !== null) {
                $transacao = app(\App\Actions\UpdateTransacaoAction::class)->execute($titulo, [
                    'valor_bruto' => $valor, 'forma_pagamento' => $data['forma_pagamento'], 'status' => StatusTransacao::Pago->value,
                    'data_pagamento' => $dataPagamento,
                ]);
            } else {
            $transacao = $this->createTransacao->execute([
                'tipo'             => TipoTransacao::Saida->value,
                'fase'             => FaseTransacao::Operacao->value,
                'categoria'        => 'servicos',
                'subcategoria'     => $fornecedorNome,
                'descricao'        => 'Pagamento de contrato — ' . $fornecedorNome . ' — ' . $competencia,
                'fornecedor_id'    => $contrato->fornecedor_id,
                'valor_bruto'      => $valor,
                'forma_pagamento'  => $data['forma_pagamento'],
                'data_competencia' => $dataCompetencia,
                'data_pagamento'   => $dataPagamento,
                'status'           => StatusTransacao::Pago->value,
                'observacoes'      => $data['observacoes'] ?: 'Pagamento de contrato lançado automaticamente.',
                'contrato_id'      => $contrato->id,
            ]);
            }

            // O pagamento do mês é registrado pela sincronização do lançamento; completa os dados
            $pagamento = ContratoPagamento::query()->firstOrNew(['contrato_id' => $contrato->id, 'competencia' => $competencia]);
            $pagamento->fill([
                'valor'           => $valor,
                'data_pagamento'  => $dataPagamento,
                'forma_pagamento' => $data['forma_pagamento'],
                'transacao_id'    => $transacao->id,
                'observacoes'     => $data['observacoes'] ?: null,
            ])->save();

            return $pagamento;
        });
    }
}
