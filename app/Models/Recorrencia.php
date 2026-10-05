<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FaseTransacao;
use App\Enums\FormaPagamento;
use App\Enums\RecorrenciaTransacao;
use App\Enums\TipoTransacao;
use App\Models\Concerns\BelongsToClinica;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Modelo de lançamento que se repete (aluguel, salário, internet...). */
class Recorrencia extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'recorrencias';

    protected $fillable = [
        'tipo', 'fase', 'categoria', 'descricao', 'paciente_id', 'fornecedor_id',
        'valor_bruto', 'forma_pagamento', 'frequencia', 'dia_vencimento', 'proxima_data',
        'data_fim', 'lancar_como_pago', 'ativa', 'encerrada_em', 'observacoes',
    ];

    protected $casts = [
        'tipo'             => TipoTransacao::class,
        'fase'             => FaseTransacao::class,
        'forma_pagamento'  => FormaPagamento::class,
        'frequencia'       => RecorrenciaTransacao::class,
        'valor_bruto'      => 'decimal:2',
        'dia_vencimento'   => 'integer',
        'proxima_data'     => 'immutable_date',
        'data_fim'         => 'immutable_date',
        'lancar_como_pago' => 'boolean',
        'ativa'            => 'boolean',
        'encerrada_em'     => 'datetime',
    ];

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Transacao::class, 'recorrencia_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function encerrada(): bool
    {
        return $this->encerrada_em !== null;
    }

    public function pausada(): bool
    {
        return ! $this->ativa && ! $this->encerrada();
    }

    /**
     * Data da ocorrência seguinte a $data: soma o intervalo da frequência e usa o dia de vencimento,
     * limitado ao último dia do mês (dia 31 → 30/04, 28/02...).
     */
    public function dataSeguinte(CarbonInterface $data): CarbonImmutable
    {
        $mes = CarbonImmutable::parse($data)->startOfMonth()->addMonthsNoOverflow($this->frequencia->meses());

        return $mes->day(min($this->dia_vencimento, $mes->daysInMonth));
    }

    /** Valor mensal equivalente (ex.: anual de 1.200 = 100/mês), para os totais da tela. */
    public function valorMensal(): float
    {
        return (float) $this->valor_bruto / max(1, $this->frequencia->meses());
    }
}
