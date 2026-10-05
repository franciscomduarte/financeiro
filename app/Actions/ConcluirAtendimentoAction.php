<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FaseTransacao;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\Agendamento;
use App\Models\Procedimento;
use App\Models\Transacao;
use App\Services\AgendamentoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Marca o agendamento como realizado e, opcionalmente, lança a receita do atendimento
 * no financeiro — tudo na mesma transação. Cada agendamento gera no máximo uma receita.
 */
class ConcluirAtendimentoAction
{
    public function __construct(
        private readonly AgendamentoService $agendamentoService,
        private readonly CreateTransacaoAction $createTransacao,
        private readonly \App\Actions\Pacotes\UsarSessaoPacoteAction $usarSessao,
    ) {}

    /**
     * @param  array{valor_bruto: float|string, categoria: string, forma_pagamento: string, pago: bool}|null  $receita
     *         null = concluir sem lançar receita
     * @param  string|null  $pacoteId  usa uma sessão deste pacote (a receita já entrou na venda do pacote)
     */
    public function execute(string $agendamentoId, ?array $receita, ?string $pacoteId = null): Agendamento
    {
        if ($receita !== null && $pacoteId !== null) {
            throw new RuntimeException('Atendimento pago com pacote não gera outra receita.');
        }

        return DB::transaction(function () use ($agendamentoId, $receita, $pacoteId): Agendamento {
            $agendamento = Agendamento::with(['paciente:id,nome'])->lockForUpdate()->findOrFail($agendamentoId);

            if (! $agendamento->status->isPendente()) {
                throw new RuntimeException("Agendamento com status '{$agendamento->status->label()}' não pode ser concluído.");
            }

            if ($receita !== null) {
                if (Transacao::where('agendamento_id', $agendamento->id)->exists()) {
                    throw new RuntimeException('Este atendimento já possui receita lançada.');
                }

                $hoje      = now()->toDateString();
                $transacao = $this->createTransacao->execute([
                    'tipo'             => TipoTransacao::Entrada->value,
                    'fase'             => FaseTransacao::Operacao->value,
                    'categoria'        => $receita['categoria'],
                    'descricao'        => $this->descricao($agendamento),
                    'cliente'          => $agendamento->paciente?->nome,
                    'paciente_id'      => $agendamento->paciente_id,
                    'agendamento_id'   => $agendamento->id,
                    'valor_bruto'      => (float) $receita['valor_bruto'],
                    'forma_pagamento'  => $receita['forma_pagamento'],
                    'data_competencia' => $agendamento->inicio_em->toDateString(),
                    'data_pagamento'   => $receita['pago'] ? $hoje : null,
                    'status'           => $receita['pago'] ? StatusTransacao::Pago->value : StatusTransacao::Pendente->value,
                ]);

                Log::info('[ConcluirAtendimento] receita lançada', [
                    'user_id'        => auth()->id(),
                    'agendamento_id' => $agendamento->id,
                    'transacao_id'   => $transacao->id,
                    'valor_bruto'    => $transacao->valor_bruto,
                ]);
            }

            if ($pacoteId !== null) {
                $this->usarSessao->execute($pacoteId, $agendamento);
            }

            return $this->agendamentoService->marcarRealizado($agendamento);
        });
    }

    /** Soma dos valores dos procedimentos do agendamento (sugestão de valor da receita). */
    public static function valorSugerido(Agendamento $agendamento): float
    {
        $ids = $agendamento->procedimentos_ids ?: [$agendamento->procedimento_id];

        return (float) Procedimento::whereIn('id', $ids)->sum('valor');
    }

    private function descricao(Agendamento $agendamento): string
    {
        $ids   = $agendamento->procedimentos_ids ?: [$agendamento->procedimento_id];
        $nomes = Procedimento::whereIn('id', $ids)->orderBy('nome')->pluck('nome')->implode(' + ');

        return mb_substr("Atendimento: {$nomes}", 0, 255);
    }
}
