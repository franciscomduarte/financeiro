<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IndiceReajuste;
use App\Enums\PeriodicidadeReajuste;
use App\Enums\RiscoContrato;
use App\Enums\StatusContrato;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contrato extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'contratos';

    protected $fillable = [
        'fornecedor_id',
        'valor_mensal',
        'dia_vencimento',
        'data_inicio',
        'data_fim',
        'periodicidade_reajuste',
        'data_proximo_reajuste',
        'indice_reajuste',
        'multa_rescisao_valor',
        'multa_rescisao_percentual',
        'aviso_previo_dias',
        'arquivo_contrato_path',
        'arquivo_contrato_nome',
        'link_contrato',
        'risco',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'status'                   => StatusContrato::class,
        'risco'                    => RiscoContrato::class,
        'periodicidade_reajuste'   => PeriodicidadeReajuste::class,
        'indice_reajuste'          => IndiceReajuste::class,
        'valor_mensal'             => 'decimal:2',
        'dia_vencimento'           => 'integer',
        'multa_rescisao_valor'     => 'decimal:2',
        'multa_rescisao_percentual'=> 'decimal:2',
        'data_inicio'              => 'date',
        'data_fim'                 => 'date',
        'data_proximo_reajuste'    => 'date',
        'aviso_previo_dias'        => 'integer',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function reajustes(): HasMany
    {
        return $this->hasMany(ContratoReajuste::class, 'contrato_id')->orderBy('data_reajuste', 'desc');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(ContratoPagamento::class, 'contrato_id')->orderBy('competencia', 'desc');
    }

    public function temArquivo(): bool
    {
        return $this->arquivo_contrato_path !== null;
    }

    public function temLink(): bool
    {
        return $this->link_contrato !== null;
    }

    public function proximoVencimento(): ?Carbon
    {
        if ($this->dia_vencimento === null) {
            return null;
        }

        $hoje      = now()->startOfDay();
        $candidato = $hoje->copy()->day($this->dia_vencimento);

        // Se o dia já passou neste mês, o próximo vencimento é no mês seguinte
        if ($candidato->lt($hoje)) {
            $candidato = $candidato->addMonthNoOverflow();
        }

        return $candidato;
    }

    public function diasParaVencimento(): ?int
    {
        $proximo = $this->proximoVencimento();
        if ($proximo === null) {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays($proximo, false);
    }
}
