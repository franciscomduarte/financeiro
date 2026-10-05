<?php

declare(strict_types=1);

namespace App\Actions\Prontuario;

use App\Models\Agendamento;
use App\Models\Paciente;
use InvalidArgumentException;

/** Atendimento opcional ligado a um registro do prontuário: precisa ser do mesmo paciente. */
trait ResolverAtendimento
{
    private function atendimentoDoPaciente(Paciente $paciente, ?string $agendamentoId): ?Agendamento
    {
        if ($agendamentoId === null || $agendamentoId === '') {
            return null;
        }

        return Agendamento::query()
            ->select(['id', 'paciente_id', 'profissional_id', 'procedimento_id', 'inicio_em'])
            ->where('paciente_id', $paciente->id)
            ->find($agendamentoId)
            ?? throw new InvalidArgumentException('Esse atendimento não é deste paciente.');
    }
}
