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

class StoreTransacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo'                    => ['required', Rule::enum(TipoTransacao::class)],
            'fase'                    => ['required', Rule::enum(FaseTransacao::class)],
            'categoria'               => ['required', 'string', 'max:100'],
            'subcategoria'            => ['nullable', 'string', 'max:100'],
            'centro_custo'            => ['nullable', 'string', 'max:100'],
            'descricao'               => ['required', 'string', 'max:255'],
            'cliente'                 => ['nullable', 'string', 'max:150'],
            'fornecedor_id'           => ['nullable', 'uuid', 'exists:fornecedores,id'],
            'paciente_id'             => ['nullable', 'uuid', 'exists:pacientes,id'],
            'valor_bruto'             => ['required', 'numeric', 'min:0.01'],
            'data_competencia'        => ['required', 'date'],
            'data_pagamento'          => ['nullable', 'date'], // se pago sem data, a Action usa hoje
            'forma_pagamento'         => ['required', Rule::enum(FormaPagamento::class)],
            'num_parcelas'            => ['sometimes', 'integer', 'min:1', 'max:12'],
            'parcela_atual'           => ['sometimes', 'integer', 'min:1'],
            'status'                  => ['sometimes', Rule::enum(StatusTransacao::class)],
            'recorrencia'             => ['sometimes', Rule::enum(RecorrenciaTransacao::class)],
            'data_inicio_recorrencia' => ['nullable', 'date'],
            'transacao_pai_id'        => ['nullable', 'uuid', 'exists:transacoes,id'],
            'observacoes'             => ['nullable', 'string', 'max:1000'],
        ];
    }
}
