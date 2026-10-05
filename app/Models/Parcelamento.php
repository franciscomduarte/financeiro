<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parcelamento extends Model
{
    use BelongsToClinica;

    protected $table = 'parcelamentos';

    protected $fillable = [
        'paciente_id',
        'descricao',
        'valor_total',
        'valor_parcela',
        'total_parcelas',
        'dia_cobranca',
        'data_inicio',
        'status',
    ];

    protected $casts = [
        'valor_total'    => 'decimal:2',
        'valor_parcela'  => 'decimal:2',
        'total_parcelas' => 'integer',
        'dia_cobranca'   => 'integer',
        'data_inicio'    => 'date',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class, 'parcelamento_id');
    }

    // ─── Atributos computados (requerem withCount carregado) ────

    public function parcelasEnviadas(): int
    {
        return $this->cobrancas()->count();
    }

    public function parcelasRecebidas(): int
    {
        return $this->cobrancas()->where('status', 'RECEIVED')->count();
    }

    public function proximaNumeroParcela(): int
    {
        return $this->parcelasEnviadas() + 1;
    }

    public function podeDisparar(): bool
    {
        return $this->status === 'ativo'
            && $this->proximaNumeroParcela() <= $this->total_parcelas;
    }

    public function verificarConclusao(): void
    {
        if ($this->status === 'ativo' && $this->parcelasRecebidas() >= $this->total_parcelas) {
            $this->update(['status' => 'concluido']);
        }
    }
}
