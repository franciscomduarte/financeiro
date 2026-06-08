<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcedimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome'             => ['required', 'string', 'max:150'],
            'descricao'        => ['nullable', 'string', 'max:1000'],
            'duracao_minutos'  => ['required', 'integer', 'min:15', 'max:480'],
            'valor'            => ['required', 'numeric', 'min:0'],
            'ativo'            => ['nullable', 'boolean'],
        ];
    }
}
