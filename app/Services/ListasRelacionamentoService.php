<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusAgendamento;
use App\Enums\StatusPaciente;
use App\Enums\TipoContatoRelacionamento;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\PesquisaSatisfacao;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Listas do dia para a recepção: retornos a chamar, aniversariantes, pacientes sumidos e
 * atendimentos sem pesquisa de satisfação. Consultas partem dos Models (clínica e escopo do
 * profissional aplicados) e escondem quem já foi contatado.
 */
class ListasRelacionamentoService
{
    public const LIMITE = 100;
    private const STATUS_FUTURO = [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value];

    /** Retorno vence de 30 dias atrás até daqui a 7 dias, sem agendamento futuro e sem lembrete enviado. */
    public function retornos(): Collection
    {
        $hoje = CarbonImmutable::today();

        $ultimas = Agendamento::query()
            ->join('procedimentos', 'procedimentos.id', '=', 'agendamentos.procedimento_id')
            ->where('agendamentos.status', StatusAgendamento::Realizado->value)
            ->whereNotNull('procedimentos.retorno_dias')
            ->selectRaw('distinct on (agendamentos.paciente_id, agendamentos.procedimento_id)
                agendamentos.id as agendamento_id, agendamentos.paciente_id, agendamentos.inicio_em as ultima_visita,
                procedimentos.nome as procedimento, (agendamentos.inicio_em::date + procedimentos.retorno_dias) as retorno_em')
            ->orderBy('agendamentos.paciente_id')->orderBy('agendamentos.procedimento_id')->orderByDesc('agendamentos.inicio_em');

        return $this->comPaciente(DB::query()->fromSub($ultimas, 'u'), 'u.paciente_id')
            ->whereBetween('u.retorno_em', [$hoje->subDays(30)->toDateString(), $hoje->addDays(7)->toDateString()])
            ->whereNotExists(fn (QueryBuilder $q) => $this->agendamentoFuturo($q, 'u.paciente_id'))
            ->whereNotExists(fn (QueryBuilder $q) => $this->jaContatado($q, 'u.paciente_id', TipoContatoRelacionamento::Retorno)->whereRaw('c.referencia = u.agendamento_id::text'))
            ->select(['u.agendamento_id', 'u.ultima_visita', 'u.procedimento', 'u.retorno_em', ...$this->colunasPaciente()])
            ->orderBy('u.retorno_em')
            ->limit(self::LIMITE)
            ->get();
    }

    /** Aniversariantes de hoje até os próximos 7 dias que ainda não receberam parabéns neste ano. */
    public function aniversarios(): Collection
    {
        $hoje  = CarbonImmutable::today();
        $dias  = collect(range(0, 7))->map(fn ($d) => $hoje->addDays($d)->format('m-d'))->all();
        $ano   = (string) $hoje->year;

        return Paciente::query()
            ->where('status', StatusPaciente::Ativo)
            ->whereNull('anonimizado_em')
            ->whereNotNull('data_nascimento')
            ->whereIn(DB::raw("to_char(data_nascimento, 'MM-DD')"), $dias)
            ->whereNotExists(fn (QueryBuilder $q) => $this->jaContatado($q, 'pacientes.id', TipoContatoRelacionamento::Aniversario)->where('c.referencia', $ano))
            ->select(['id', 'nome', 'telefone', 'data_nascimento', 'aceita_whatsapp_marketing'])
            ->orderByRaw("to_char(data_nascimento, 'MM-DD')")
            ->limit(self::LIMITE)
            ->get()
            ->sortBy(fn (Paciente $p) => array_search($p->data_nascimento->format('m-d'), $dias, true))
            ->values();
    }

    /** Pacientes ativos sem atendimento há N meses, sem nada marcado e sem convite nos últimos 60 dias. */
    public function sumidos(int $meses): Collection
    {
        $limite = CarbonImmutable::today()->subMonths($meses);

        $ultimas = Agendamento::query()
            ->where('status', StatusAgendamento::Realizado->value)
            ->groupBy('agendamentos.paciente_id')
            ->selectRaw('agendamentos.paciente_id, max(agendamentos.inicio_em) as ultima_visita, count(*) as visitas');

        return $this->comPaciente(DB::query()->fromSub($ultimas, 'u'), 'u.paciente_id')
            ->where('u.ultima_visita', '<', $limite)
            ->whereNotExists(fn (QueryBuilder $q) => $this->agendamentoFuturo($q, 'u.paciente_id'))
            ->whereNotExists(fn (QueryBuilder $q) => $q->from('relacionamento_contatos as c')->whereColumn('c.paciente_id', 'u.paciente_id')
                ->where('c.tipo', TipoContatoRelacionamento::Sumido->value)
                ->where('c.created_at', '>=', CarbonImmutable::now()->subDays(60)))
            ->select(['u.ultima_visita', 'u.visitas', ...$this->colunasPaciente()])
            ->orderByDesc('u.ultima_visita')
            ->limit(self::LIMITE)
            ->get();
    }

    /** Atendimentos realizados nos últimos 7 dias que ainda não receberam a pesquisa. */
    public function semPesquisa(): Collection
    {
        return Agendamento::query()
            ->with(['paciente:id,nome,telefone', 'procedimento:id,nome', 'profissional:id,nome'])
            ->select(['id', 'paciente_id', 'profissional_id', 'procedimento_id', 'inicio_em'])
            ->where('status', StatusAgendamento::Realizado->value)
            ->whereBetween('inicio_em', [CarbonImmutable::today()->subDays(7), CarbonImmutable::now()])
            ->whereDoesntHave('pesquisa')
            ->whereHas('paciente', fn ($q) => $q->whereNull('anonimizado_em'))
            ->orderByDesc('inicio_em')
            ->limit(self::LIMITE)
            ->get();
    }

    /** NPS dos últimos N dias: % promotores (9–10) − % detratores (0–6). */
    public function resultadoPesquisas(int $dias = 90): array
    {
        $desde = CarbonImmutable::now()->subDays($dias);

        $r = PesquisaSatisfacao::query()
            ->whereNotNull('respondida_em')->where('respondida_em', '>=', $desde)
            ->selectRaw('count(*) as respostas, avg(nota) as media,
                sum(case when nota >= 9 then 1 else 0 end) as promotores,
                sum(case when nota <= 6 then 1 else 0 end) as detratores')
            ->first();

        $enviadas  = PesquisaSatisfacao::query()->whereNotNull('enviada_em')->where('enviada_em', '>=', $desde)->count();
        $respostas = (int) $r->respostas;

        return [
            'enviadas'   => $enviadas,
            'respostas'  => $respostas,
            'media'      => $respostas ? round((float) $r->media, 1) : null,
            'nps'        => $respostas ? (int) round(((int) $r->promotores - (int) $r->detratores) / $respostas * 100) : null,
            'comentarios' => PesquisaSatisfacao::query()
                ->with(['paciente:id,nome', 'profissional:id,nome'])
                ->select(['id', 'paciente_id', 'profissional_id', 'nota', 'comentario', 'respondida_em'])
                ->whereNotNull('respondida_em')->where('respondida_em', '>=', $desde)
                ->orderByDesc('respondida_em')
                ->limit(20)
                ->get(),
        ];
    }

    private function comPaciente(QueryBuilder $q, string $coluna): QueryBuilder
    {
        // Pacientes já filtrados pela clínica e pelo escopo do profissional
        return $q->joinSub(
            Paciente::query()->where('status', StatusPaciente::Ativo)->whereNull('anonimizado_em')
                ->select(['id', 'nome', 'telefone', 'aceita_whatsapp_marketing']),
            'p', 'p.id', '=', $coluna,
        );
    }

    /** @return array<int, string> */
    private function colunasPaciente(): array
    {
        return ['p.id as paciente_id', 'p.nome', 'p.telefone', 'p.aceita_whatsapp_marketing'];
    }

    private function agendamentoFuturo(QueryBuilder $q, string $coluna): void
    {
        $q->from('agendamentos as f')->whereColumn('f.paciente_id', $coluna)
            ->whereIn('f.status', self::STATUS_FUTURO)
            ->where('f.inicio_em', '>=', CarbonImmutable::now());
    }

    private function jaContatado(QueryBuilder $q, string $coluna, TipoContatoRelacionamento $tipo): QueryBuilder
    {
        return $q->from('relacionamento_contatos as c')->whereColumn('c.paciente_id', $coluna)->where('c.tipo', $tipo->value);
    }
}
