<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBloqueioAgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inicio_em' => ['required', 'date_format:Y-m-d H:i'],
            'fim_em'    => ['required', 'date_format:Y-m-d H:i', 'after:inicio_em'],
            'motivo'    => ['nullable', 'string', 'max:500'],
        ];
    }
}
