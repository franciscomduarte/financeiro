<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\IndiceReajuste;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReajusteContratoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_reajuste' => ['required', 'date'],
            'valor_novo'    => ['required', 'numeric', 'min:0.01'],
            'indice'        => ['nullable', Rule::enum(IndiceReajuste::class)],
            'observacoes'   => ['nullable', 'string'],
        ];
    }
}
