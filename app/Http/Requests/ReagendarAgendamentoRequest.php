<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReagendarAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nova_data'     => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'novo_horario'  => ['required', 'date_format:H:i'],
        ];
    }
}
