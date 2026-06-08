<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfissionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome'       => ['required', 'string', 'max:150'],
            'email'      => ['required', 'email', 'max:200', 'unique:profissionais,email'],
            'telefone'   => ['nullable', 'string', 'max:20'],
            'cor_agenda' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'ativo'      => ['nullable', 'boolean'],
        ];
    }
}
