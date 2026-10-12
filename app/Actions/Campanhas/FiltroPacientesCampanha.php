<?php

declare(strict_types=1);

namespace App\Actions\Campanhas;

use App\Enums\StatusAgendamento;
use App\Models\CampanhaDestinatario;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pacientes que podem receber campanha (com celular e não anonimizados) e os filtros da tela.
 * Também marca/desmarca em lote os pacientes do filtro como destinatários.
 */
class FiltroPacientesCampanha
{
    /** Máximo de pacientes marcados de uma vez pelo "marcar todos". */
    public const LIMITE_MARCAR_TODOS = 2000;

    /**
     * @param array{busca?: string, aniversario_mes?: int|string|null, procedimento_id?: int|string|null, sem_vir_dias?: int|string|null,
     *              origem?: string|null, so_autorizados?: bool, so_selecionados?: bool} $f
     */
    public function consulta(string $campanhaId, array $f): Builder
    {
        $busca = trim((string) ($f['busca'] ?? ''));
        $mes = (int) ($f['aniversario_mes'] ?? 0);
        $procedimento = (int) ($f['procedimento_id'] ?? 0);
        $semVir = (int) ($f['sem_vir_dias'] ?? 0);
        $origem = trim((string) ($f['origem'] ?? ''));

        return Paciente::query()
            ->whereNull('anonimizado_em')
            ->whereRaw("length(regexp_replace(coalesce(telefone, ''), '\\D', '', 'g')) >= 10")
            ->when($busca !== '', fn ($q) => $q->where('nome', 'ilike', '%' . str_replace(['%', '_'], ['\%', '\_'], $busca) . '%'))
            ->when($mes >= 1 && $mes <= 12, fn ($q) => $q->whereRaw('extract(month from data_nascimento) = ?', [$mes]))
            ->when($procedimento > 0, fn ($q) => $q->whereExists(fn ($a) => $a->selectRaw('1')->from('agendamentos')
                ->whereColumn('agendamentos.paciente_id', 'pacientes.id')
                ->whereColumn('agendamentos.tenant_id', 'pacientes.tenant_id')
                ->where('agendamentos.status', StatusAgendamento::Realizado->value)
                ->where(fn ($p) => $p->where('agendamentos.procedimento_id', $procedimento)
                    ->orWhereRaw('agendamentos.procedimentos_ids @> ?::jsonb', [json_encode([$procedimento])]))))
            ->when($semVir > 0, fn ($q) => $q
                ->whereExists(fn ($a) => $this->realizados($a))
                ->whereNotExists(fn ($a) => $this->realizados($a)->where('agendamentos.inicio_em', '>=', now()->subDays($semVir)))
                ->whereNotExists(fn ($a) => $a->selectRaw('1')->from('agendamentos') // já tem horário marcado: não precisa chamar
                    ->whereColumn('agendamentos.paciente_id', 'pacientes.id')
                    ->whereColumn('agendamentos.tenant_id', 'pacientes.tenant_id')
                    ->whereIn('agendamentos.status', [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value])
                    ->where('agendamentos.inicio_em', '>=', now())))
            ->when($origem !== '', fn ($q) => $q->where('origem', $origem))
            ->when(! empty($f['so_autorizados']), fn ($q) => $q->where('aceita_whatsapp_marketing', true))
            ->when(! empty($f['so_selecionados']), fn ($q) => $q->whereIn('id', CampanhaDestinatario::query()
                ->select('paciente_id')->where('campanha_id', $campanhaId)));
    }

    /** Marca todos os pacientes do filtro (até o limite); devolve quantos entraram agora. */
    public function marcarTodos(string $campanhaId, string $tenantId, array $f): int
    {
        $ids = $this->consulta($campanhaId, $f)
            ->whereNotIn('id', CampanhaDestinatario::query()->select('paciente_id')->where('campanha_id', $campanhaId))
            ->orderBy('nome')->limit(self::LIMITE_MARCAR_TODOS)->pluck('id');

        return DB::transaction(function () use ($ids, $campanhaId, $tenantId): int {
            $inseridos = 0;
            foreach ($ids->chunk(500) as $lote) {
                $agora = now();
                $inseridos += CampanhaDestinatario::query()->insertOrIgnore($lote->map(fn ($id) => [
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'campanha_id' => $campanhaId, 'paciente_id' => $id,
                    'status' => 'pendente', 'created_at' => $agora, 'updated_at' => $agora,
                ])->values()->all());
            }

            return $inseridos;
        });
    }

    /** Desmarca os pacientes do filtro; devolve quantos saíram. */
    public function desmarcarTodos(string $campanhaId, array $f): int
    {
        return CampanhaDestinatario::query()->where('campanha_id', $campanhaId)
            ->whereIn('paciente_id', $this->consulta($campanhaId, $f)->select('id'))
            ->delete();
    }

    private function realizados($a)
    {
        return $a->selectRaw('1')->from('agendamentos')
            ->whereColumn('agendamentos.paciente_id', 'pacientes.id')
            ->whereColumn('agendamentos.tenant_id', 'pacientes.tenant_id')
            ->where('agendamentos.status', StatusAgendamento::Realizado->value);
    }
}
