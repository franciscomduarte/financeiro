<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paciente_id'    => ['required', 'uuid', 'exists:pacientes,id'],
            'profissional_id' => ['required', 'uuid', 'exists:profissionais,id'],
            'procedimento_id' => ['required', 'integer', 'exists:procedimentos,id'],
            'inicio_em'      => ['required', 'date_format:Y-m-d H:i'],
            'observacoes'    => ['nullable', 'string', 'max:1000'],
        ];
    }
}
