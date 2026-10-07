<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormularioLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // página pública da clínica
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nome'            => ['required', 'string', 'min:2', 'max:150'],
            'telefone'        => ['required', 'string', 'max:20', 'regex:/^[\d\s()+\-.]{10,20}$/'],
            'email'           => ['nullable', 'email', 'max:150'],
            'procedimento_id' => ['nullable', 'integer'],
            'mensagem'        => ['nullable', 'string', 'max:1000'],
            'consentimento'   => ['accepted'],
            'origem'          => ['nullable', 'string', 'max:20'],
            'site'            => ['prohibited'], // armadilha para robôs (campo escondido)
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome.required'          => 'Informe seu nome.',
            'telefone.required'      => 'Informe seu WhatsApp com DDD.',
            'telefone.regex'         => 'Informe seu WhatsApp com DDD. Ex.: (61) 99999-0000',
            'email.email'            => 'Confira o e-mail.',
            'consentimento.accepted' => 'Para a clínica entrar em contato, marque a autorização.',
        ];
    }
}
