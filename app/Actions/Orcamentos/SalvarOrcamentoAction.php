<?php

declare(strict_types=1);

namespace App\Actions\Orcamentos;

use App\Enums\StatusOrcamento;
use App\Models\Clinica;
use App\Models\Orcamento;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/** Cria ou altera um orçamento em aberto (itens, desconto, validade). Total calculado aqui, nunca vindo da tela. */
class SalvarOrcamentoAction
{
    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    /**
     * @param  array{paciente_id: string, validade: string, desconto: float, observacoes?: ?string, forma_pagamento?: ?string,
     *               itens: array<int, array{procedimento_id?: ?string, descricao: string, quantidade: int, valor_unitario: float}>}  $dados
     */
    public function execute(?string $id, array $dados): Orcamento
    {
        $itens = array_values(array_filter($dados['itens'], fn ($i) => trim((string) $i['descricao']) !== ''));
        if ($itens === []) {
            throw new InvalidArgumentException('Adicione pelo menos um item ao orçamento.');
        }

        return DB::transaction(function () use ($id, $dados, $itens): Orcamento {
            Paciente::query()->select(['id'])->findOrFail($dados['paciente_id']);

            $procedimentos = Procedimento::query()->select(['id'])
                ->whereIn('id', array_map('intval', array_filter(array_column($itens, 'procedimento_id'))))->pluck('id')->all();

            $linhas = array_map(fn (array $i) => [
                'procedimento_id' => in_array((int) ($i['procedimento_id'] ?? 0), $procedimentos, true) ? (int) $i['procedimento_id'] : null,
                'descricao'       => mb_substr(trim($i['descricao']), 0, 150),
                'quantidade'      => max(1, (int) $i['quantidade']),
                'valor_unitario'  => round((float) $i['valor_unitario'], 2),
                'subtotal'        => round(max(1, (int) $i['quantidade']) * (float) $i['valor_unitario'], 2),
            ], $itens);

            $subtotal = round(array_sum(array_column($linhas, 'subtotal')), 2);
            $desconto = round(max(0, (float) $dados['desconto']), 2);
            if ($desconto > $subtotal) {
                throw new InvalidArgumentException('O desconto não pode passar do valor dos itens.');
            }

            if ($id !== null) {
                $orcamento = Orcamento::query()->lockForUpdate()->findOrFail($id);
                if ($orcamento->status !== StatusOrcamento::Aberto) {
                    throw new RuntimeException('Só orçamentos em aberto podem ser alterados.');
                }
                $orcamento->itens()->delete();
            } else {
                $orcamento = new Orcamento([
                    'numero'  => $this->proximoNumero(),
                    'user_id' => auth()->id(),
                    'status'  => StatusOrcamento::Aberto,
                ]);
            }

            $orcamento->fill([
                'paciente_id'     => $dados['paciente_id'],
                'validade'        => $dados['validade'],
                'subtotal'        => $subtotal,
                'desconto'        => $desconto,
                'total'           => round($subtotal - $desconto, 2),
                'observacoes'     => $dados['observacoes'] ?? null,
                'forma_pagamento' => $dados['forma_pagamento'] ?? null,
            ])->save();

            $orcamento->itens()->createMany($linhas);

            return $orcamento;
        });
    }

    /** Número sequencial por clínica; a trava na linha da clínica evita números repetidos. */
    private function proximoNumero(): int
    {
        Clinica::query()->whereKey($this->clinicaAtual->id())->lockForUpdate()->first(['id']);

        return (int) Orcamento::query()->max('numero') + 1;
    }
}
