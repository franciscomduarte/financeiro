<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxaCartaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'percentual' => ['required', 'numeric', 'min:0', 'max:100'],
            'ativo'      => ['sometimes', 'boolean'],
        ];
    }
}
