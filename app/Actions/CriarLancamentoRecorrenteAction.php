<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FormaPagamento;
use App\Enums\RecorrenciaTransacao;
use App\Models\Recorrencia;
use App\Models\Transacao;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Novo lançamento que se repete: grava o primeiro lançamento (a data informada), cria o modelo
 * da recorrência e já gera os do mês atual. Meses anteriores ao atual não são criados.
 */
class CriarLancamentoRecorrenteAction
{
    public function __construct(
        private readonly CreateTransacaoAction $criarLancamento,
        private readonly GerarLancamentosRecorrentesAction $gerar,
    ) {}

    /**
     * @param  array<string, mixed>  $data  mesmos campos do CreateTransacaoAction, com 'recorrencia' ≠ 'unica'
     */
    public function execute(array $data, ?string $dataFim = null, bool $lancarComoPago = false): Transacao
    {
        $frequencia = RecorrenciaTransacao::from($data['recorrencia']);
        if (! $frequencia->repete()) {
            throw new InvalidArgumentException('Escolha com que frequência o lançamento se repete.');
        }
        if (FormaPagamento::from($data['forma_pagamento'])->parcelas() > 1) {
            throw new InvalidArgumentException('Lançamentos parcelados no cartão não podem se repetir. Use outra forma de pagamento.');
        }

        $primeira = CarbonImmutable::parse($data['data_competencia'])->startOfDay();
        $fim      = $dataFim ? CarbonImmutable::parse($dataFim)->startOfDay() : null;
        if ($fim !== null && $fim->lt($primeira)) {
            throw new InvalidArgumentException('A data final da repetição precisa ser depois do primeiro lançamento.');
        }

        return DB::transaction(function () use ($data, $frequencia, $primeira, $fim, $lancarComoPago): Transacao {
            $recorrencia = Recorrencia::create([
                'tipo'             => $data['tipo'],
                'fase'             => $data['fase'],
                'categoria'        => $data['categoria'],
                'descricao'        => $data['descricao'],
                'paciente_id'      => $data['paciente_id'] ?? null,
                'fornecedor_id'    => $data['fornecedor_id'] ?? null,
                'valor_bruto'      => $data['valor_bruto'],
                'forma_pagamento'  => $data['forma_pagamento'],
                'frequencia'       => $frequencia,
                'dia_vencimento'   => $primeira->day,
                'proxima_data'     => $primeira,
                'data_fim'         => $fim,
                'lancar_como_pago' => $lancarComoPago,
                'observacoes'      => $data['observacoes'] ?? null,
            ]);

            // 1º lançamento: exatamente como a pessoa preencheu (situação e pagamento inclusive)
            $primeiro = $this->criarLancamento->execute($data + ['recorrencia_id' => $recorrencia->id]);

            // Próximas datas: pula as que ficaram antes do mês atual (sem criar atrasados)
            $proxima      = $recorrencia->dataSeguinte($primeira);
            $inicioDoMes  = CarbonImmutable::today()->startOfMonth();
            while ($proxima->lt($inicioDoMes)) {
                $proxima = $recorrencia->dataSeguinte($proxima);
            }
            $recorrencia->update(['proxima_data' => $proxima]);

            $this->gerar->gerar($recorrencia->id, CarbonImmutable::today()->endOfMonth()->startOfDay());

            Log::info('[Recorrencias] recorrência criada', [
                'recorrencia_id' => $recorrencia->id,
                'frequencia'     => $frequencia->value,
                'user_id'        => auth()->id(),
            ]);

            return $primeiro;
        });
    }
}
