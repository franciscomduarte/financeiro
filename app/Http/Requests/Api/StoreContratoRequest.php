<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\IndiceReajuste;
use App\Enums\PeriodicidadeReajuste;
use App\Enums\RiscoContrato;
use App\Enums\StatusContrato;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContratoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fornecedor_id'              => ['required', 'uuid', 'exists:fornecedores,id'],
            'valor_mensal'               => ['required', 'numeric', 'min:0'],
            'data_inicio'                => ['required', 'date'],
            'data_fim'                   => ['nullable', 'date', 'after:data_inicio'],
            'periodicidade_reajuste'     => ['nullable', Rule::enum(PeriodicidadeReajuste::class)],
            'data_proximo_reajuste'      => ['nullable', 'date'],
            'indice_reajuste'            => ['nullable', Rule::enum(IndiceReajuste::class)],
            'multa_rescisao_valor'       => ['nullable', 'numeric', 'min:0'],
            'multa_rescisao_percentual'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'aviso_previo_dias'          => ['nullable', 'integer', 'min:0'],
            'link_contrato'              => ['nullable', 'url', 'max:2000'],
            'risco'                      => ['nullable', Rule::enum(RiscoContrato::class)],
            'status'                     => ['nullable', Rule::enum(StatusContrato::class)],
            'observacoes'                => ['nullable', 'string'],
        ];
    }
}
