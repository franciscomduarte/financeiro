<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\StatusFornecedor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFornecedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome_fantasia'                => ['required', 'string', 'max:150'],
            'razao_social'                 => ['nullable', 'string', 'max:200'],
            'cnpj'                         => ['nullable', 'string', 'max:20', Rule::unique('fornecedores', 'cnpj')],
            'servico_prestado'             => ['required', 'string', 'max:200'],
            'categoria'                    => ['nullable', 'string', 'max:100'],
            'contato_nome'                 => ['nullable', 'string', 'max:150'],
            'contato_telefone'             => ['nullable', 'string', 'max:20'],
            'contato_email'                => ['nullable', 'email', 'max:150'],
            'contato_emergencia_nome'      => ['nullable', 'string', 'max:150'],
            'contato_emergencia_telefone'  => ['nullable', 'string', 'max:20'],
            'status'                       => ['nullable', Rule::enum(StatusFornecedor::class)],
            'observacoes'                  => ['nullable', 'string'],
        ];
    }
}
