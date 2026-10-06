<?php

declare(strict_types=1);

namespace App\Actions\Atendimento;

use App\Enums\StatusAtendimento;
use App\Enums\VisibilidadeAtendimento;
use App\Models\Agendamento;
use App\Models\Atendimento;
use App\Models\Paciente;
use App\Support\ClinicaAtual;
use App\Support\EscopoProfissional;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Abre o atendimento (da agenda ou avulso). Se o horário já tem um em andamento, volta para ele. */
class IniciarAtendimentoAction
{
    public function __construct(
        private readonly ClinicaAtual $clinicaAtual,
        private readonly GarantirFichasPadraoAction $fichasPadrao,
    ) {}

    public function execute(string $pacienteId, ?string $agendamentoId = null): Atendimento
    {
        $this->clinicaAtual->garantirEscrita();
        $this->fichasPadrao->execute();

        return DB::transaction(function () use ($pacienteId, $agendamentoId): Atendimento {
            $paciente = Paciente::query()->select(['id', 'anonimizado_em'])->findOrFail($pacienteId);
            if ($paciente->anonimizado_em !== null) {
                throw new RuntimeException('Este paciente foi anonimizado e não recebe novos registros.');
            }

            $agendamento = null;
            if ($agendamentoId !== null) {
                $agendamento = Agendamento::query()->select(['id', 'paciente_id', 'profissional_id', 'status'])
                    ->where('paciente_id', $paciente->id)->lockForUpdate()->find($agendamentoId)
                    ?? throw new RuntimeException('Esse horário não é deste paciente.');

                $aberto = Atendimento::query()->where('agendamento_id', $agendamento->id)
                    ->where('status', StatusAtendimento::EmAndamento)->first();
                if ($aberto !== null) {
                    return $aberto;
                }
            }

            $atendimento = Atendimento::create([
                'paciente_id'     => $paciente->id,
                'agendamento_id'  => $agendamento?->id,
                'profissional_id' => $agendamento?->profissional_id ?? app(EscopoProfissional::class)->profissionalId(),
                'user_id'         => auth()->id(),
                'status'          => StatusAtendimento::EmAndamento,
                'visibilidade'    => VisibilidadeAtendimento::Privado,
                'iniciado_em'     => now(),
            ]);

            Log::info('[Atendimento] iniciado', ['atendimento_id' => $atendimento->id, 'paciente_id' => $paciente->id, 'user_id' => auth()->id()]);

            return $atendimento;
        });
    }
}
