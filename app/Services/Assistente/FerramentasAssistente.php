<?php

declare(strict_types=1);

namespace App\Services\Assistente;

use App\Actions\Leads\BuscarPacienteDoLead;
use App\Enums\EtapaLead;
use App\Enums\StatusAgendamento;
use App\Enums\TipoInteracaoLead;
use App\Jobs\AvisarEquipeLeadJob;
use App\Models\Agendamento;
use App\Models\AssistenteConfiguracao;
use App\Models\Lead;
use App\Models\LeadInteracao;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Services\AgendamentoService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ferramentas que o assistente pode usar na conversa: ver horários livres, agendar a avaliação e
 * passar o atendimento para a equipe. Em simulação (tela de teste), nada é gravado nem enviado.
 */
class FerramentasAssistente
{
    private const DIAS_BUSCA = 7;
    private const HORARIOS_POR_DIA = 6;

    public function __construct(private readonly AgendamentoService $agenda) {}

    /** @return list<array<string, mixed>> */
    public function definicoes(AssistenteConfiguracao $config): array
    {
        $ferramentas = [[
            'name'        => 'passar_para_equipe',
            'description' => 'Passa a conversa para uma pessoa da equipe e pausa o assistente neste contato. Use quando a pessoa pedir para falar com alguém, '
                . 'reclamar, fizer pergunta de saúde/médica, pedir desconto ou condição especial, ou perguntar algo que não está nas informações da clínica.',
            'inputSchema' => [
                'type'                 => 'object',
                'properties'           => ['motivo' => ['type' => 'string', 'description' => 'Resumo curto do motivo, para a equipe (ex.: "pediu desconto no pacote de botox").']],
                'required'             => ['motivo'],
                'additionalProperties' => false,
            ],
        ]];

        if ($config->podeAgendar()) {
            $ferramentas[] = [
                'name'        => 'consultar_horarios',
                'description' => 'Lista os horários livres para a avaliação nos próximos dias. Use antes de oferecer ou confirmar qualquer horário.',
                'inputSchema' => [
                    'type'                 => 'object',
                    'properties'           => ['a_partir_de' => ['type' => ['string', 'null'], 'description' => 'Data inicial no formato AAAA-MM-DD, ou null para hoje.']],
                    'required'             => ['a_partir_de'],
                    'additionalProperties' => false,
                ],
            ];
            $ferramentas[] = [
                'name'        => 'agendar_avaliacao',
                'description' => 'Agenda a avaliação num horário livre (confirme antes com consultar_horarios) depois que a pessoa escolheu o dia e a hora.',
                'inputSchema' => [
                    'type'                 => 'object',
                    'properties'           => [
                        'data'          => ['type' => 'string', 'description' => 'AAAA-MM-DD'],
                        'hora'          => ['type' => 'string', 'description' => 'HH:MM'],
                        'nome_completo' => ['type' => ['string', 'null'], 'description' => 'Nome completo, se a pessoa informou; senão null.'],
                    ],
                    'required'             => ['data', 'hora', 'nome_completo'],
                    'additionalProperties' => false,
                ],
            ];
        }

        return $ferramentas;
    }

    /** @param array<string, mixed> $entrada */
    public function executar(?Lead $lead, AssistenteConfiguracao $config, string $nome, array $entrada, bool $simulacao = false): string
    {
        try {
            return match ($nome) {
                'consultar_horarios' => $config->podeAgendar() ? $this->horarios($config, $entrada['a_partir_de'] ?? null) : 'Agendamento pelo assistente está desligado.',
                'agendar_avaliacao'  => $config->podeAgendar() ? $this->agendar($lead, $config, (string) ($entrada['data'] ?? ''), (string) ($entrada['hora'] ?? ''), $entrada['nome_completo'] ?? null, $simulacao) : 'Agendamento pelo assistente está desligado.',
                'passar_para_equipe' => $this->passarParaEquipe($lead, (string) ($entrada['motivo'] ?? ''), $simulacao),
                default              => "Ferramenta desconhecida: {$nome}.",
            };
        } catch (Throwable $e) {
            Log::error('[Assistente] erro na ferramenta', ['ferramenta' => $nome, 'lead_id' => $lead?->id, 'erro' => $e->getMessage()]);

            return 'Não foi possível concluir agora. Peça desculpas e use passar_para_equipe.';
        }
    }

    /** @return Collection<int, Profissional> */
    private function profissionais(AssistenteConfiguracao $config): Collection
    {
        return Profissional::query()->select(['id', 'nome'])->where('ativo', true)
            ->when($config->profissional_id, fn ($q, $id) => $q->whereKey($id))
            ->orderBy('nome')->limit(10)->get();
    }

    private function duracao(AssistenteConfiguracao $config): int
    {
        return max(15, (int) ($config->procedimentoAvaliacao?->duracao_minutos ?? 30));
    }

    /** Horários livres de um dia (todas as profissionais), sem os que já passaram. @return array<string, list<string>> hora => [profissional_id] */
    private function livresNoDia(AssistenteConfiguracao $config, CarbonImmutable $dia): array
    {
        $limite = now()->addHour();
        $livres = [];
        foreach ($this->profissionais($config) as $p) {
            foreach ($this->agenda->slotsDisponiveis($p->id, $dia->toDateString(), $this->duracao($config)) as $hora) {
                if ($dia->setTimeFromTimeString($hora)->gt($limite)) {
                    $livres[$hora][] = $p->id;
                }
            }
        }
        ksort($livres);

        return $livres;
    }

    private function horarios(AssistenteConfiguracao $config, ?string $aPartirDe): string
    {
        $inicio = rescue(fn () => CarbonImmutable::createFromFormat('!Y-m-d', (string) $aPartirDe), null, false) ?: CarbonImmutable::today();
        $inicio = $inicio->lt(CarbonImmutable::today()) ? CarbonImmutable::today() : $inicio;

        $linhas = [];
        for ($d = 0; $d < self::DIAS_BUSCA; $d++) {
            $dia   = $inicio->addDays($d);
            $horas = array_slice(array_keys($this->livresNoDia($config, $dia)), 0, self::HORARIOS_POR_DIA);
            if ($horas !== []) {
                $linhas[] = ucfirst($dia->locale('pt_BR')->translatedFormat('l, d/m')) . ' (' . $dia->toDateString() . '): ' . implode(', ', $horas);
            }
        }

        return $linhas === []
            ? 'Nenhum horário livre nos próximos ' . self::DIAS_BUSCA . ' dias a partir de ' . $inicio->format('d/m') . '. Ofereça passar para a equipe.'
            : "Horários livres para avaliação ({$this->duracao($config)} min):\n" . implode("\n", $linhas);
    }

    private function agendar(?Lead $lead, AssistenteConfiguracao $config, string $data, string $hora, ?string $nomeCompleto, bool $simulacao): string
    {
        $dia = rescue(fn () => CarbonImmutable::createFromFormat('!Y-m-d', $data), null, false);
        if (! $dia || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) {
            return 'Data ou hora inválida. Use consultar_horarios e tente de novo.';
        }
        $profissionalId = ($this->livresNoDia($config, $dia)[$hora] ?? [])[0] ?? null;
        if ($profissionalId === null) {
            return "O horário {$dia->format('d/m')} às {$hora} não está mais livre. Consulte os horários de novo e ofereça outro.";
        }
        $quando = $dia->format('d/m') . " às {$hora}";
        if ($simulacao || $lead === null) {
            return "(Simulação) Avaliação seria agendada para {$quando}.";
        }

        $pacienteId = Lead::query()->whereKey($lead->id)->value('paciente_id'); // pode ter mudado nesta mesma conversa
        $existente  = $pacienteId ? Agendamento::query()->select(['id', 'inicio_em'])->where('paciente_id', $pacienteId)
            ->whereIn('status', [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value])
            ->where('inicio_em', '>=', now())->orderBy('inicio_em')->first() : null;
        if ($existente) {
            return 'Já existe uma avaliação agendada para ' . $existente->inicio_em->format('d/m \à\s H:i') . '. Não agende outra; se a pessoa quiser mudar, use passar_para_equipe.';
        }

        $agendamento = DB::transaction(function () use ($lead, $config, $dia, $hora, $nomeCompleto, $profissionalId, $quando): Agendamento {
            $lead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
            $paciente = ($lead->paciente_id ? Paciente::query()->find($lead->paciente_id) : null)
                ?? BuscarPacienteDoLead::porContato($lead->telefone_chave, $lead->email)
                ?? Paciente::create([
                    'nome'     => filled($nomeCompleto) ? mb_substr(trim((string) $nomeCompleto), 0, 150) : $lead->nome,
                    'telefone' => $lead->telefone,
                    'email'    => $lead->email,
                    'origem'   => $lead->origem->label(),
                ]);

            $agendamento = $this->agenda->criar([
                'paciente_id'       => $paciente->id,
                'profissional_id'   => $profissionalId,
                'procedimentos_ids' => [(int) $config->procedimento_avaliacao_id],
                'procedimento_id'   => (int) $config->procedimento_avaliacao_id,
                'inicio_em'         => $dia->toDateString() . " {$hora}",
                'observacoes'       => 'Agendado pelo assistente do WhatsApp.',
            ]);

            $lead->update([
                'paciente_id' => $paciente->id, 'etapa' => EtapaLead::AvaliacaoAgendada, 'proximo_contato_em' => null,
                'ultima_interacao_em' => now(), 'primeiro_contato_em' => $lead->primeiro_contato_em ?? now(),
            ]);
            LeadInteracao::create(['lead_id' => $lead->id, 'tipo' => TipoInteracaoLead::Nota, 'texto' => "Assistente agendou a avaliação para {$quando}."]);

            return $agendamento;
        });

        Log::info('[Assistente] avaliação agendada', ['lead_id' => $lead->id, 'agendamento_id' => $agendamento->id]);
        AvisarEquipeLeadJob::dispatch($lead->id, "📅 O assistente agendou a avaliação de {$lead->nome} para {$quando}.")->afterCommit();

        return "Agendado com sucesso para {$quando}. Confirme com a pessoa (ela também recebe a confirmação automática).";
    }

    private function passarParaEquipe(?Lead $lead, string $motivo, bool $simulacao): string
    {
        $motivo = mb_substr(trim($motivo) ?: 'Pediu atendimento da equipe', 0, 200);
        if ($simulacao || $lead === null) {
            return "(Simulação) Equipe seria avisada: {$motivo}. Diga que alguém da equipe vai continuar o atendimento.";
        }

        $lead->update(['assistente_pausado_em' => now(), 'assistente_motivo' => $motivo, 'proximo_contato_em' => now()]);
        LeadInteracao::create(['lead_id' => $lead->id, 'tipo' => TipoInteracaoLead::Nota, 'texto' => "Assistente passou para a equipe: {$motivo}"]);
        AvisarEquipeLeadJob::dispatch($lead->id, "🙋 {$lead->nome} precisa de atendimento da equipe no WhatsApp.\nMotivo: {$motivo}");

        return 'Equipe avisada e assistente pausado neste contato. Diga, de forma breve, que alguém da equipe vai continuar o atendimento em breve.';
    }
}
