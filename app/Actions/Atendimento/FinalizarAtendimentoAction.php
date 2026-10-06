<?php

declare(strict_types=1);

namespace App\Actions\Atendimento;

use App\Enums\StatusAtendimento;
use App\Enums\StockMovementTypeEnum;
use App\Models\Atendimento;
use App\Models\AtendimentoInjetavel;
use App\Models\PlanoTratamento;
use App\Models\StockBatch;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Fecha o atendimento: dá baixa no estoque dos injetáveis, grava a duração e tranca o registro.
 * Cancelar descarta o rascunho (as fotos ficam no prontuário).
 */
class FinalizarAtendimentoAction
{
    use AtendimentoEditavel;

    public function __construct(private readonly StockService $estoque) {}

    public function finalizar(string $atendimentoId): Atendimento
    {
        return DB::transaction(function () use ($atendimentoId): Atendimento {
            $atendimento = $this->atendimentoEditavel($atendimentoId, travar: true);
            $procedimento = 'Atendimento ' . $atendimento->iniciado_em->timezone(config('clinica.fuso_horario'))->format('d/m/Y');

            $atendimento->injetaveis()->with('produto:id,name')->get()->each(function (AtendimentoInjetavel $item) use ($atendimento, $procedimento): void {
                $quantidade = (float) $item->quantidade;
                try {
                    if ($item->batch_id !== null) {
                        $mov    = $this->estoque->manualExit($item->batch_id, $quantidade, StockMovementTypeEnum::ProcedureUse, $atendimento->paciente_id, $procedimento, auth()->id());
                        $lotes  = [$mov->batch_id];
                    } else {
                        $lotes = array_column($this->estoque->consumeForProcedure($item->product_id, $quantidade, $procedimento, $atendimento->paciente_id, auth()->id()), 'batch_id');
                    }
                } catch (RuntimeException $e) {
                    throw new RuntimeException("Estoque de {$item->produto?->name}: {$e->getMessage()} Ajuste a quantidade ou o lote antes de finalizar.", 0, $e);
                }

                $numeros = StockBatch::query()->whereKey($lotes)->pluck('lot_number')->filter()->implode(', ');
                $item->update(['lotes_baixados' => mb_substr($numeros, 0, 255) ?: null]);
            });

            $agora = now();
            $atendimento->update([
                'status'           => StatusAtendimento::Finalizado,
                'finalizado_em'    => $agora,
                'duracao_segundos' => max(0, (int) $atendimento->iniciado_em->diffInSeconds($agora)),
            ]);

            Log::info('[Atendimento] finalizado', ['atendimento_id' => $atendimento->id, 'paciente_id' => $atendimento->paciente_id, 'user_id' => auth()->id()]);

            return $atendimento;
        });
    }

    public function cancelar(string $atendimentoId): void
    {
        DB::transaction(function () use ($atendimentoId): void {
            $atendimento = $this->atendimentoEditavel($atendimentoId, travar: true);

            if (PlanoTratamento::query()->where('atendimento_id', $atendimento->id)->whereNotNull('orcamento_id')->exists()) {
                throw new RuntimeException('O plano deste atendimento já virou orçamento. Finalize o atendimento em vez de cancelar.');
            }
            PlanoTratamento::query()->where('atendimento_id', $atendimento->id)->delete();
            $atendimento->delete(); // fichas e injetáveis vão junto; fotos ficam no prontuário

            Log::warning('[Atendimento] cancelado', ['atendimento_id' => $atendimento->id, 'paciente_id' => $atendimento->paciente_id, 'user_id' => auth()->id()]);
        });
    }

    public function alterarVisibilidade(string $atendimentoId, \App\Enums\VisibilidadeAtendimento $visibilidade): void
    {
        $atendimento = $this->atendimentoEditavel($atendimentoId, exigirAberto: false);
        $atendimento->update(['visibilidade' => $visibilidade]);
    }
}
