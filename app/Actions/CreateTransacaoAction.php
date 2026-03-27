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

            $transacao = Transacao::create([
                'tipo'                    => $tipo,
                'fase'                    => $data['fase'],
                'categoria'               => $data['categoria'],
                'subcategoria'            => $data['subcategoria'] ?? null,
                'centro_custo'            => $data['centro_custo'] ?? null,
                'descricao'               => $data['descricao'],
                'cliente'                 => $data['cliente'] ?? null,
                'fornecedor_id'           => $data['fornecedor_id'] ?? null,
                'valor_bruto'             => $data['valor_bruto'],
                'taxa_operacional'        => $valores['taxa_operacional'],
                'imposto_estimado'        => $valores['imposto_estimado'],
                'valor_liquido'           => $valores['valor_liquido'],
                'data_competencia'        => $data['data_competencia'],
                'data_pagamento'          => $data['data_pagamento'] ?? null,
                'forma_pagamento'         => $formaPagamento,
                'num_parcelas'            => $data['num_parcelas'] ?? 1,
                'parcela_atual'           => $data['parcela_atual'] ?? 1,
                'status'                  => StatusTransacao::from($data['status'] ?? StatusTransacao::Pendente->value),
                'recorrencia'             => $data['recorrencia'] ?? 'unica',
                'data_inicio_recorrencia' => $data['data_inicio_recorrencia'] ?? null,
                'transacao_pai_id'        => $data['transacao_pai_id'] ?? null,
                'observacoes'             => $data['observacoes'] ?? null,
            ]);

            return $transacao;
        });
    }
}
