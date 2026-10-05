<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResponderPesquisaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // página pública: o token do link identifica a pesquisa
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nota'       => ['required', 'integer', 'between:0,10'],
            'comentario' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nota.required' => 'Escolha uma nota de 0 a 10.',
            'nota.between'  => 'Escolha uma nota de 0 a 10.',
        ];
    }
}
