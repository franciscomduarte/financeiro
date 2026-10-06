<?php

declare(strict_types=1);

namespace App\Actions\Atendimento;

use App\Models\AtendimentoInjetavel;
use App\Models\StockBatch;
use App\Models\StockProduct;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/** Produtos aplicados no atendimento. A baixa no estoque acontece só ao finalizar. */
class InjetaveisAtendimentoAction
{
    use AtendimentoEditavel;

    /** @param  array{product_id: int|string, batch_id?: int|string|null, quantidade: float|string, regiao?: ?string, observacao?: ?string}  $dados */
    public function adicionar(string $atendimentoId, array $dados): AtendimentoInjetavel
    {
        $atendimento = $this->atendimentoEditavel($atendimentoId);

        $produto    = StockProduct::query()->select(['id', 'name'])->findOrFail((int) $dados['product_id']);
        $quantidade = round((float) str_replace(',', '.', (string) $dados['quantidade']), 3);
        if ($quantidade <= 0 || $quantidade > 100000) {
            throw new InvalidArgumentException('Informe a quantidade aplicada.');
        }

        $loteId = filled($dados['batch_id'] ?? null) ? (int) $dados['batch_id'] : null;
        if ($loteId !== null && ! StockBatch::query()->whereKey($loteId)->where('product_id', $produto->id)->exists()) {
            throw new InvalidArgumentException('Esse lote não é do produto escolhido.');
        }

        $item = AtendimentoInjetavel::create([
            'atendimento_id' => $atendimento->id,
            'product_id'     => $produto->id,
            'batch_id'       => $loteId,
            'quantidade'     => $quantidade,
            'regiao'         => filled($dados['regiao'] ?? null) ? mb_substr(trim((string) $dados['regiao']), 0, 120) : null,
            'observacao'     => filled($dados['observacao'] ?? null) ? mb_substr(trim((string) $dados['observacao']), 0, 255) : null,
        ]);

        Log::info('[Atendimento] injetável registrado', ['atendimento_id' => $atendimento->id, 'product_id' => $produto->id, 'user_id' => auth()->id()]);

        return $item;
    }

    public function remover(string $atendimentoId, string $itemId): void
    {
        $atendimento = $this->atendimentoEditavel($atendimentoId);

        AtendimentoInjetavel::query()->where('atendimento_id', $atendimento->id)->whereKey($itemId)->delete();
    }
}
