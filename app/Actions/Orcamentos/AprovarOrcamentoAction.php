<?php

declare(strict_types=1);

namespace App\Actions\Orcamentos;

use App\Actions\CreateTransacaoAction;
use App\Enums\FaseTransacao;
use App\Enums\FormaPagamento;
use App\Enums\StatusOrcamento;
use App\Enums\StatusPacote;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\Orcamento;
use App\Models\Pacote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Aprova o orçamento: lança uma receita com o total e transforma cada item num pacote de sessões
 * (valor do item com a parte proporcional do desconto). Os atendimentos depois usam esses pacotes.
 */
class AprovarOrcamentoAction
{
    public function __construct(private readonly CreateTransacaoAction $createTransacao) {}

    public function execute(string $orcamentoId, FormaPagamento $forma, string $categoria, bool $pago, ?string $validadePacotes = null): Orcamento
    {
        return DB::transaction(function () use ($orcamentoId, $forma, $categoria, $pago, $validadePacotes): Orcamento {
            $orcamento = Orcamento::query()->with(['paciente:id,nome', 'itens'])->lockForUpdate()->findOrFail($orcamentoId);

            if ($orcamento->status !== StatusOrcamento::Aberto) {
                throw new RuntimeException('Este orçamento já foi ' . mb_strtolower($orcamento->status->label()) . '.');
            }

            $hoje    = now()->toDateString();
            $receita = $this->createTransacao->execute([
                'tipo'             => TipoTransacao::Entrada->value,
                'fase'             => FaseTransacao::Operacao->value,
                'categoria'        => $categoria,
                'descricao'        => mb_substr("Orçamento {$orcamento->codigo()} aprovado", 0, 255),
                'cliente'          => $orcamento->paciente?->nome,
                'paciente_id'      => $orcamento->paciente_id,
                'valor_bruto'      => (float) $orcamento->total,
                'forma_pagamento'  => $forma->value,
                'data_competencia' => $hoje,
                'data_pagamento'   => $pago ? $hoje : null,
                'status'           => ($pago ? StatusTransacao::Pago : StatusTransacao::Pendente)->value,
            ]);

            // Desconto rateado pelos itens; o último item absorve o arredondamento
            $fator    = (float) $orcamento->subtotal > 0 ? (float) $orcamento->total / (float) $orcamento->subtotal : 0.0;
            $restante = (float) $orcamento->total;
            $ultimo   = $orcamento->itens->count() - 1;

            foreach ($orcamento->itens->values() as $i => $item) {
                $valor     = $i === $ultimo ? round($restante, 2) : round((float) $item->subtotal * $fator, 2);
                $restante -= $valor;

                Pacote::create([
                    'paciente_id'     => $orcamento->paciente_id,
                    'procedimento_id' => $item->procedimento_id,
                    'orcamento_id'    => $orcamento->id,
                    'transacao_id'    => $receita->id,
                    'user_id'         => auth()->id(),
                    'nome'            => $item->quantidade > 1 ? "{$item->quantidade}× {$item->descricao}" : $item->descricao,
                    'sessoes_total'   => $item->quantidade,
                    'valor_total'     => $valor,
                    'validade'        => $validadePacotes,
                    'status'          => StatusPacote::Ativo,
                ]);
            }

            $orcamento->update([
                'status'          => StatusOrcamento::Aprovado,
                'decidido_em'     => now(),
                'transacao_id'    => $receita->id,
                'forma_pagamento' => $forma,
            ]);

            Log::info('[Orcamentos] orçamento aprovado', [
                'orcamento_id' => $orcamento->id, 'transacao_id' => $receita->id, 'total' => $orcamento->total, 'user_id' => auth()->id(),
            ]);

            return $orcamento;
        });
    }
}
