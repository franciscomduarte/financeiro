<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusLancamentoFiscal;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObrigacaoFiscalLancamento extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'obrigacao_fiscal_lancamentos';

    protected $fillable = [
        'obrigacao_fiscal_id',
        'competencia',
        'data_vencimento',
        'data_pagamento',
        'valor_principal',
        'valor_multa',
        'valor_juros',
        'status',
        'numero_autenticacao',
        'codigo_barras',
        'transacao_id',
        'arquivo_path',
        'arquivo_nome',
        'observacoes',
    ];

    protected $casts = [
        'status'          => StatusLancamentoFiscal::class,
        'valor_principal' => 'decimal:2',
        'valor_multa'     => 'decimal:2',
        'valor_juros'     => 'decimal:2',
        'data_vencimento' => 'date',
        'data_pagamento'  => 'date',
    ];

    public function obrigacaoFiscal(): BelongsTo
    {
        return $this->belongsTo(ObrigacaoFiscal::class, 'obrigacao_fiscal_id');
    }

    public function transacao(): BelongsTo
    {
        return $this->belongsTo(Transacao::class, 'transacao_id');
    }

    public function valorTotal(): float
    {
        return (float) $this->valor_principal
            + (float) $this->valor_multa
            + (float) $this->valor_juros;
    }

    public function competenciaFormatada(): string
    {
        [$ano, $mes] = explode('-', $this->competencia);
        $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        return ($meses[(int)$mes - 1]) . '/' . $ano;
    }

    public function temMultaOuJuros(): bool
    {
        return (float) $this->valor_multa > 0 || (float) $this->valor_juros > 0;
    }
}
