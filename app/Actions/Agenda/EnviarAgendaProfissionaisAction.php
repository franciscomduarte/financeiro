<?php

declare(strict_types=1);

namespace App\Actions\Agenda;

use App\Enums\OrigemConfirmacao;
use App\Enums\StatusAgendamento;
use App\Mail\AgendaProfissionalMail;
use App\Models\Agendamento;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\Scopes\ProfissionalScope;
use App\Support\ClinicaAtual;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/**
 * Véspera: cada profissional ativo com e-mail recebe a agenda do dia seguinte (rodar com a clínica ativa).
 * Uma vez por dia por profissional: nova tentativa do job só reenvia o que falhou.
 */
class EnviarAgendaProfissionaisAction
{
    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    /** @return array{profissionais: int, enviados: int} */
    public function execute(?CarbonImmutable $dia = null): array
    {
        $fuso = (string) config('clinica.fuso_horario');
        $dia ??= CarbonImmutable::now($fuso)->addDay()->startOfDay();

        $agendamentos = Agendamento::query()->withoutGlobalScope(ProfissionalScope::class)
            ->with(['paciente:id,nome', 'procedimento:id,nome'])
            ->select(['id', 'paciente_id', 'profissional_id', 'procedimento_id', 'procedimentos_ids', 'inicio_em', 'fim_em',
                'status', 'observacoes', 'confirmacao_pedida_em', 'confirmacao_origem', 'pediu_remarcar_em'])
            ->whereIn('status', [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value])
            ->where('inicio_em', '>=', $dia)->where('inicio_em', '<', $dia->addDay())
            ->whereNotNull('profissional_id')
            ->orderBy('inicio_em')->limit(500)->get();
        if ($agendamentos->isEmpty()) {
            return ['profissionais' => 0, 'enviados' => 0];
        }

        $profissionais = Profissional::query()->select(['id', 'nome', 'email'])
            ->whereIn('id', $agendamentos->pluck('profissional_id')->unique())
            ->where('ativo', true)->whereNotNull('email')->where('email', '<>', '')
            ->get()->keyBy('id');
        $veteranos = $this->pacientesJaAtendidos($agendamentos->pluck('paciente_id')->filter()->unique()->values(), $dia);
        $nomesProcedimentos = $this->nomesProcedimentos($agendamentos);

        $enviados = 0;
        $falhas = 0;
        foreach ($agendamentos->groupBy('profissional_id') as $profissionalId => $doProfissional) {
            $profissional = $profissionais->get($profissionalId);
            if ($profissional === null) {
                continue;
            }
            $chave = "agenda-profissional:{$this->clinicaAtual->id()}:{$dia->toDateString()}:{$profissionalId}";
            if (Cache::has($chave)) {
                continue;
            }

            $itens = $doProfissional->map(fn (Agendamento $a) => [
                'inicio'         => $a->inicio_em->timezone($fuso)->format('H:i'),
                'fim'            => $a->fim_em?->timezone($fuso)->format('H:i'),
                'paciente'       => (string) ($a->paciente?->nome ?? '—'),
                'procedimento'   => $this->procedimentos($a, $nomesProcedimentos),
                'confirmacao'    => $this->confirmacao($a),
                'observacoes'    => filled($a->observacoes) ? (string) $a->observacoes : null,
                'primeira_vez'   => $a->paciente_id !== null && ! $veteranos->has($a->paciente_id),
            ])->values()->all();

            try {
                Mail::to((string) $profissional->email)
                    ->send(new AgendaProfissionalMail((string) $profissional->nome, $dia, $itens, (string) $this->clinicaAtual->nome()));
                Cache::put($chave, true, now()->addHours(36));
                $enviados++;
            } catch (Throwable $e) {
                $falhas++;
                Log::error('[AgendaProfissionais] falha no e-mail', ['profissional_id' => $profissionalId, 'erro' => $e->getMessage()]);
            }
        }

        Log::info('[AgendaProfissionais] agendas enviadas', ['data' => $dia->toDateString(), 'enviados' => $enviados, 'falhas' => $falhas]);
        if ($falhas > 0) {
            throw new RuntimeException("Falha ao enviar {$falhas} agenda(s) de profissional.");
        }

        return ['profissionais' => $profissionais->count(), 'enviados' => $enviados];
    }

    /** Pacientes que já tiveram algum atendimento realizado antes do dia (os demais vêm pela primeira vez). */
    private function pacientesJaAtendidos(Collection $pacienteIds, CarbonImmutable $dia): Collection
    {
        if ($pacienteIds->isEmpty()) {
            return collect();
        }

        return Agendamento::query()->withoutGlobalScope(ProfissionalScope::class)
            ->whereIn('paciente_id', $pacienteIds)
            ->where('status', StatusAgendamento::Realizado->value)
            ->where('inicio_em', '<', $dia)
            ->distinct()->pluck('paciente_id')->flip();
    }

    /** @return Collection<string, string> id => nome dos procedimentos extras (agendamento com vários) */
    private function nomesProcedimentos(Collection $agendamentos): Collection
    {
        $ids = $agendamentos->flatMap(fn (Agendamento $a) => (array) ($a->procedimentos_ids ?? []))->filter()->unique()->values();

        return $ids->isEmpty() ? collect()
            : Procedimento::query()->whereIn('id', $ids)->limit(200)->pluck('nome', 'id');
    }

    private function procedimentos(Agendamento $a, Collection $nomes): string
    {
        $lista = collect((array) ($a->procedimentos_ids ?? []))->map(fn ($id) => $nomes->get($id))->filter();
        if ($lista->isEmpty() && $a->procedimento?->nome) {
            $lista = collect([$a->procedimento->nome]);
        }

        return $lista->unique()->implode(' + ') ?: '—';
    }

    private function confirmacao(Agendamento $a): string
    {
        return match (true) {
            $a->status === StatusAgendamento::Confirmado && $a->confirmacao_origem === OrigemConfirmacao::Paciente => 'Confirmado pelo paciente',
            $a->status === StatusAgendamento::Confirmado => 'Confirmado',
            $a->pediu_remarcar_em !== null                 => 'Pediu para remarcar',
            $a->confirmacao_pedida_em !== null             => 'Sem resposta',
            default                                        => 'Aguardando confirmação',
        };
    }
}
