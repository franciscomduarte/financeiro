<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TipoAnexo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnexoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo'   => ['required', Rule::enum(TipoAnexo::class)],
            'arquivo' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,docx',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'arquivo.max'   => 'O arquivo não pode ultrapassar 10MB.',
            'arquivo.mimes' => 'Apenas arquivos PDF, JPG, PNG e DOCX são aceitos.',
        ];
    }
}
