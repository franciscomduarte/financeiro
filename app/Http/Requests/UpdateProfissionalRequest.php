<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfissionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $profissionalId = $this->route('profissional')?->id;

        return [
            'nome'       => ['sometimes', 'string', 'max:150'],
            'email'      => ['sometimes', 'email', 'max:200', "unique:profissionais,email,{$profissionalId}"],
            'telefone'   => ['nullable', 'string', 'max:20'],
            'cor_agenda' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'ativo'      => ['nullable', 'boolean'],
        ];
    }
}
