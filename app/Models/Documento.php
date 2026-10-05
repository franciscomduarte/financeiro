<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use App\Enums\StatusDocumento;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Documento extends Model
{
    use BelongsToClinica, HasUuids;

    protected $fillable = [
        'categoria_id',
        'titulo',
        'numero_documento',
        'orgao_emissor',
        'responsavel',
        'data_emissao',
        'data_validade',
        'alerta_dias_antes',
        'status',
        'arquivo_path',
        'arquivo_nome',
        'arquivo_tamanho_kb',
        'observacoes',
    ];

    protected $casts = [
        'data_emissao'      => 'date',
        'data_validade'     => 'date',
        'alerta_dias_antes' => 'integer',
        'arquivo_tamanho_kb' => 'integer',
        'status'            => StatusDocumento::class,
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(DocumentoCategoria::class, 'categoria_id');
    }

    public function versoes(): HasMany
    {
        return $this->hasMany(DocumentoVersao::class)->orderByDesc('created_at');
    }

    public function alertaDias(): int
    {
        return $this->alerta_dias_antes ?? $this->categoria?->alerta_dias_antes ?? 30;
    }

    public function diasParaVencer(): ?int
    {
        if ($this->data_validade === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->data_validade, false);
    }

    public function estaVencido(): bool
    {
        return $this->data_validade !== null && $this->diasParaVencer() < 0;
    }

    public function estaVencendo(): bool
    {
        $dias = $this->diasParaVencer();

        return $dias !== null && $dias >= 0 && $dias <= $this->alertaDias();
    }

    public function statusDisplay(): string
    {
        if ($this->status === StatusDocumento::Arquivado) {
            return 'Arquivado';
        }
        if ($this->estaVencido()) {
            return 'Vencido';
        }
        if ($this->estaVencendo()) {
            return 'Vencendo';
        }

        return $this->status->label();
    }

    public function statusCorClasses(): string
    {
        if ($this->status === StatusDocumento::Arquivado) {
            return 'bg-stone-100 text-stone-600';
        }
        if ($this->estaVencido()) {
            return 'bg-red-100 text-red-700';
        }
        if ($this->estaVencendo()) {
            return 'bg-amber-100 text-amber-700';
        }
        if ($this->status === StatusDocumento::Renovando) {
            return 'bg-amber-100 text-amber-700';
        }

        return 'bg-green-100 text-green-700';
    }

    public function temArquivo(): bool
    {
        return $this->arquivo_path !== null;
    }
}
