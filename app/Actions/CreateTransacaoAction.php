<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FormaPagamento;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\Transacao;
use Illuminate\Support\Facades\DB;

class CreateTransacaoAction
{
    public function __construct(
        private readonly CalcularValoresTransacaoAction $calcularValores,
    ) {}

    public function execute(array $data): Transacao
    {
        return DB::transaction(function () use ($data): Transacao {
            $tipo           = TipoTransacao::from($data['tipo']);
            $formaPagamento = FormaPagamento::from($data['forma_pagamento']);

            $valores = $this->calcularValores->execute(
                valorBruto:      (float) $data['valor_bruto'],
                formaPagamento:  $formaPagamento,
                tipo:            $tipo,
            );

            $status = StatusTransacao::from($data['status'] ?? StatusTransacao::Pendente->value);

            $transacao = new Transacao();
            $transacao->anteciparCartao = isset($data['antecipar_cartao']) ? (bool) $data['antecipar_cartao'] : null;
            $transacao->fill([
                'tipo'                    => $tipo,
                'fase'                    => $data['fase'],
                'categoria'               => $data['categoria'],
                'subcategoria'            => $data['subcategoria'] ?? null,
                'centro_custo'            => $data['centro_custo'] ?? null,
                'descricao'               => $data['descricao'],
                'cliente'                 => $data['cliente'] ?? null,
                'paciente_id'             => $data['paciente_id'] ?? null,
                'agendamento_id'          => $data['agendamento_id'] ?? null,
                'fornecedor_id'           => $data['fornecedor_id'] ?? null,
                'contrato_id'             => $data['contrato_id'] ?? null,
                'valor_bruto'             => $data['valor_bruto'],
                'taxa_operacional'        => $valores['taxa_operacional'],
                'imposto_estimado'        => $valores['imposto_estimado'],
                'valor_liquido'           => $valores['valor_liquido'],
                'data_competencia'        => $data['data_competencia'],
                'data_vencimento'         => $data['data_vencimento'] ?? $data['data_competencia'],
                'conta_financeira_id'     => $data['conta_financeira_id'] ?? null,
                // Pago sempre tem data de pagamento (o Dashboard soma por ela)
                'data_pagamento'          => $data['data_pagamento']
                    ?? ($status === StatusTransacao::Pago ? now()->toDateString() : null),
                'forma_pagamento'         => $formaPagamento,
                'num_parcelas'            => $formaPagamento->parcelas() > 1 ? $formaPagamento->parcelas() : ($data['num_parcelas'] ?? 1),
                'parcela_atual'           => $data['parcela_atual'] ?? 1,
                'status'                  => $status,
                'recorrencia'             => $data['recorrencia'] ?? 'unica',
                'data_inicio_recorrencia' => $data['data_inicio_recorrencia'] ?? null,
                'transacao_pai_id'        => $data['transacao_pai_id'] ?? null,
                'recorrencia_id'          => $data['recorrencia_id'] ?? null,
                'observacoes'             => $data['observacoes'] ?? null,
            ]);
            $transacao->save();

            return $transacao;
        });
    }
}
