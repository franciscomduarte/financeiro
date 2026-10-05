<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/** Autocadastro "Assine já": clínica + primeiro administrador. */
class CadastroClinicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email'   => mb_strtolower(trim((string) $this->input('email'))),
            'clinica' => trim((string) $this->input('clinica')),
            'nome'    => trim((string) $this->input('nome')),
        ]);
    }

    public function rules(): array
    {
        return [
            'clinica'  => ['required', 'string', 'min:2', 'max:150'],
            'nome'     => ['required', 'string', 'min:2', 'max:150'],
            'email'    => ['required', 'email:rfc', 'max:150', 'unique:users,email'],
            'celular'  => ['required', 'string', 'max:30', 'regex:/^[\d\s()+-]+$/', 'regex:/(\D*\d){10,}/'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'site'     => ['prohibited'], // armadilha para robôs: campo invisível que pessoas não preenchem
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'   => 'Já existe uma conta com este e-mail. Entre com sua senha ou use "Esqueci minha senha".',
            'celular.regex'  => 'Informe o celular com DDD (ex.: (61) 99999-0000).',
            'site.prohibited' => 'Não foi possível concluir o cadastro.',
        ];
    }

    public function attributes(): array
    {
        return [
            'clinica'  => 'nome da clínica',
            'nome'     => 'seu nome',
            'email'    => 'e-mail',
            'celular'  => 'celular',
            'password' => 'senha',
        ];
    }
}
