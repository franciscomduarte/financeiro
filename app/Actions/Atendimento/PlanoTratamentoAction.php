<?php

declare(strict_types=1);

namespace App\Actions\Atendimento;

use App\Actions\Orcamentos\SalvarOrcamentoAction;
use App\Models\Orcamento;
use App\Models\PlanoTratamento;
use App\Models\Procedimento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/** Plano de tratamento do atendimento: salvar os itens e transformar em orçamento. */
class PlanoTratamentoAction
{
    use AtendimentoEditavel;

    public const MAX_ITENS = 30;

    public function __construct(private readonly SalvarOrcamentoAction $salvarOrcamento) {}

    /** @param  array<int, array{procedimento_id?: int|string|null, descricao: string, sessoes: int|string, intervalo_dias?: int|string|null, valor_unitario: float|string}>  $itens */
    public function salvar(string $atendimentoId, array $itens, ?string $observacoes): PlanoTratamento
    {
        $atendimento = $this->atendimentoEditavel($atendimentoId);

        $itens = array_values(array_filter($itens, fn ($i) => trim((string) ($i['descricao'] ?? '')) !== ''));
        if (count($itens) > self::MAX_ITENS) {
            throw new InvalidArgumentException('O plano pode ter até ' . self::MAX_ITENS . ' itens.');
        }

        $procedimentos = Procedimento::query()->whereIn('id', array_map('intval', array_filter(array_column($itens, 'procedimento_id'))))->pluck('id')->all();

        return DB::transaction(function () use ($atendimento, $itens, $observacoes, $procedimentos): PlanoTratamento {
            $plano = PlanoTratamento::query()->firstOrCreate(
                ['atendimento_id' => $atendimento->id],
                ['paciente_id' => $atendimento->paciente_id, 'user_id' => auth()->id()],
            );
            if ($plano->orcamento_id !== null) {
                throw new RuntimeException('Este plano já virou orçamento. Altere pelo orçamento.');
            }

            $plano->update(['observacoes' => filled($observacoes) ? mb_substr(trim($observacoes), 0, 2000) : null]);
            $plano->itens()->delete();
            foreach ($itens as $ordem => $i) {
                $sessoes = max(1, min(100, (int) $i['sessoes']));
                $plano->itens()->create([
                    'procedimento_id' => in_array((int) ($i['procedimento_id'] ?? 0), $procedimentos, true) ? (int) $i['procedimento_id'] : null,
                    'descricao'       => mb_substr(trim((string) $i['descricao']), 0, 150),
                    'sessoes'         => $sessoes,
                    'intervalo_dias'  => filled($i['intervalo_dias'] ?? null) ? max(1, min(365, (int) $i['intervalo_dias'])) : null,
                    'valor_unitario'  => round(max(0, (float) str_replace(',', '.', (string) $i['valor_unitario'])), 2),
                    'ordem'           => $ordem,
                ]);
            }

            return $plano->load('itens');
        });
    }

    /** Cria o orçamento com os itens do plano (cada sessão é uma unidade). */
    public function gerarOrcamento(string $atendimentoId): Orcamento
    {
        $atendimento = $this->atendimentoEditavel($atendimentoId, exigirAberto: false); // vale também depois de finalizado

        return DB::transaction(function () use ($atendimento): Orcamento {
            $plano = PlanoTratamento::query()->with('itens')->where('atendimento_id', $atendimento->id)->lockForUpdate()->first();
            if ($plano === null || $plano->itens->isEmpty()) {
                throw new RuntimeException('Salve o plano com pelo menos um procedimento antes de gerar o orçamento.');
            }
            if ($plano->orcamento_id !== null) {
                throw new RuntimeException('Este plano já virou orçamento.');
            }

            $orcamento = $this->salvarOrcamento->execute(null, [
                'paciente_id' => $atendimento->paciente_id,
                'validade'    => now()->addDays(15)->toDateString(),
                'desconto'    => 0,
                'observacoes' => $plano->observacoes,
                'itens'       => $plano->itens->map(fn ($i) => [
                    'procedimento_id' => $i->procedimento_id,
                    'descricao'       => $i->descricao . ($i->intervalo_dias ? " (a cada {$i->intervalo_dias} dias)" : ''),
                    'quantidade'      => $i->sessoes,
                    'valor_unitario'  => (float) $i->valor_unitario,
                ])->all(),
            ]);
            $plano->update(['orcamento_id' => $orcamento->id]);

            Log::info('[Atendimento] plano virou orçamento', ['atendimento_id' => $atendimento->id, 'orcamento_id' => $orcamento->id, 'user_id' => auth()->id()]);

            return $orcamento;
        });
    }
}
