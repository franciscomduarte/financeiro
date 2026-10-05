<?php

declare(strict_types=1);

namespace App\Actions\Prontuario;

use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\ProntuarioEvolucao;

/** Registra a evolução de um atendimento no prontuário. Depois de salva, não muda. */
class RegistrarEvolucaoAction
{
    use ResolverAtendimento;

    public function execute(Paciente $paciente, string $texto, ?string $agendamentoId = null): ProntuarioEvolucao
    {
        $atendimento = $this->atendimentoDoPaciente($paciente, $agendamentoId);

        return ProntuarioEvolucao::create([
            'paciente_id'     => $paciente->id,
            'agendamento_id'  => $atendimento?->id,
            'profissional_id' => $atendimento?->profissional_id
                ?? Profissional::query()->where('user_id', auth()->id())->value('id'),
            'user_id'         => auth()->id(),
            'texto'           => trim($texto),
        ]);
    }
}
