<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\FaseTransacao;
use App\Enums\FormaPagamento;
use App\Enums\RecorrenciaTransacao;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo'                    => ['sometimes', Rule::enum(TipoTransacao::class)],
            'fase'                    => ['sometimes', Rule::enum(FaseTransacao::class)],
            'categoria'               => ['sometimes', 'string', 'max:100'],
            'subcategoria'            => ['nullable', 'string', 'max:100'],
            'centro_custo'            => ['nullable', 'string', 'max:100'],
            'descricao'               => ['sometimes', 'string', 'max:255'],
            'cliente'                 => ['nullable', 'string', 'max:150'],
            'fornecedor_id'           => ['nullable', 'uuid'],
            'valor_bruto'             => ['sometimes', 'numeric', 'min:0.01'],
            'data_competencia'        => ['sometimes', 'date'],
            'data_pagamento'          => ['nullable', 'date'],
            'forma_pagamento'         => ['sometimes', Rule::enum(FormaPagamento::class)],
            'num_parcelas'            => ['sometimes', 'integer', 'min:1', 'max:12'],
            'parcela_atual'           => ['sometimes', 'integer', 'min:1'],
            'status'                  => ['sometimes', Rule::enum(StatusTransacao::class)],
            'recorrencia'             => ['sometimes', Rule::enum(RecorrenciaTransacao::class)],
            'data_inicio_recorrencia' => ['nullable', 'date'],
            'transacao_pai_id'        => ['nullable', 'uuid', 'exists:transacoes,id'],
            'observacoes'             => ['nullable', 'string'],
        ];
    }
}
