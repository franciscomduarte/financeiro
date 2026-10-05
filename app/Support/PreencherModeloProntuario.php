<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Agendamento;
use App\Models\Paciente;

/**
 * Preenche os campos de um modelo de termo ou orientação com os dados do paciente:
 * {paciente}, {cpf}, {data_nascimento}, {data}, {clinica}, {profissional} e {procedimento}.
 */
class PreencherModeloProntuario
{
    public const CAMPOS = ['{paciente}', '{cpf}', '{data_nascimento}', '{data}', '{clinica}', '{profissional}', '{procedimento}'];

    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    public function preencher(string $texto, Paciente $paciente, ?Agendamento $agendamento = null): string
    {
        $agendamento?->loadMissing(['profissional:id,nome', 'procedimento:id,nome']);

        return strtr($texto, [
            '{paciente}'        => $paciente->nome,
            '{cpf}'             => $paciente->cpf ?: '___________',
            '{data_nascimento}' => $paciente->data_nascimento?->format('d/m/Y') ?? '___/___/_____',
            '{data}'            => now()->format('d/m/Y'),
            '{clinica}'         => $this->clinicaAtual->nome(),
            '{profissional}'    => $agendamento?->profissional?->nome ?? '___________',
            '{procedimento}'    => $agendamento?->procedimento?->nome ?? '___________',
        ]);
    }
}
