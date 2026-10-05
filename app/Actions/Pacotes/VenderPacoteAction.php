<?php

declare(strict_types=1);

namespace App\Actions\Pacotes;

use App\Actions\CreateTransacaoAction;
use App\Enums\FaseTransacao;
use App\Enums\StatusPacote;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\Pacote;
use App\Models\Paciente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Vende um pacote de sessões: lança a receita do pacote (uma vez, na venda) e cria o saldo de sessões.
 * Os atendimentos que usam o pacote não geram receita de novo.
 */
class VenderPacoteAction
{
    public function __construct(private readonly CreateTransacaoAction $createTransacao) {}

    /**
     * @param  array{paciente_id: string, procedimento_id?: ?int, nome: string, sessoes: int, valor_total: float,
     *               validade?: ?string, forma_pagamento: string, categoria: string, pago: bool}  $dados
     */
    public function execute(array $dados): Pacote
    {
        if ($dados['sessoes'] < 1 || $dados['valor_total'] <= 0) {
            throw new InvalidArgumentException('Informe a quantidade de sessões e o valor do pacote.');
        }

        return DB::transaction(function () use ($dados): Pacote {
            $paciente = Paciente::query()->select(['id', 'nome'])->findOrFail($dados['paciente_id']);
            $hoje     = now()->toDateString();

            $receita = $this->createTransacao->execute([
                'tipo'             => TipoTransacao::Entrada->value,
                'fase'             => FaseTransacao::Operacao->value,
                'categoria'        => $dados['categoria'],
                'descricao'        => mb_substr("Pacote: {$dados['nome']} ({$dados['sessoes']} sessões)", 0, 255),
                'cliente'          => $paciente->nome,
                'paciente_id'      => $paciente->id,
                'valor_bruto'      => round($dados['valor_total'], 2),
                'forma_pagamento'  => $dados['forma_pagamento'],
                'data_competencia' => $hoje,
                'data_pagamento'   => $dados['pago'] ? $hoje : null,
                'status'           => ($dados['pago'] ? StatusTransacao::Pago : StatusTransacao::Pendente)->value,
            ]);

            $pacote = Pacote::create([
                'paciente_id'     => $paciente->id,
                'procedimento_id' => $dados['procedimento_id'] ?? null,
                'orcamento_id'    => $dados['orcamento_id'] ?? null,
                'transacao_id'    => $receita->id,
                'user_id'         => auth()->id(),
                'nome'            => trim($dados['nome']),
                'sessoes_total'   => $dados['sessoes'],
                'valor_total'     => round($dados['valor_total'], 2),
                'validade'        => $dados['validade'] ?? null,
                'status'          => StatusPacote::Ativo,
            ]);

            Log::info('[Pacotes] pacote vendido', [
                'pacote_id' => $pacote->id, 'paciente_id' => $paciente->id, 'transacao_id' => $receita->id, 'user_id' => auth()->id(),
            ]);

            return $pacote;
        });
    }
}
