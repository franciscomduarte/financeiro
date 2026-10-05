<?php

declare(strict_types=1);

namespace App\Actions\Prontuario;

use App\Models\Paciente;
use App\Models\ProntuarioOrientacao;

/** Registra as orientações entregues ao paciente (pós-procedimento, prescrição). */
class RegistrarOrientacaoAction
{
    use ResolverAtendimento;

    public function execute(Paciente $paciente, string $titulo, string $texto, ?string $agendamentoId = null): ProntuarioOrientacao
    {
        $atendimento = $this->atendimentoDoPaciente($paciente, $agendamentoId);

        return ProntuarioOrientacao::create([
            'paciente_id'    => $paciente->id,
            'agendamento_id' => $atendimento?->id,
            'user_id'        => auth()->id(),
            'titulo'         => trim($titulo),
            'texto'          => trim($texto),
        ]);
    }
}
