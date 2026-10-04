<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use App\Enums\PeriodicidadeFiscal;
use App\Enums\TipoTributo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObrigacaoFiscal extends Model
{
    use BelongsToClinica, HasFactory, HasUuids;

    protected $table = 'obrigacoes_fiscais';

    protected $fillable = [
        'tipo_tributo',
        'descricao',
        'codigo_receita',
        'periodicidade',
        'dia_vencimento',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'tipo_tributo'   => TipoTributo::class,
        'periodicidade'  => PeriodicidadeFiscal::class,
        'dia_vencimento' => 'integer',
    ];

    public function lancamentos(): HasMany
    {
        return $this->hasMany(ObrigacaoFiscalLancamento::class, 'obrigacao_fiscal_id')->orderByDesc('competencia');
    }

    public function ultimoLancamento(): HasMany
    {
        return $this->hasMany(ObrigacaoFiscalLancamento::class, 'obrigacao_fiscal_id')->orderByDesc('competencia')->limit(1);
    }

    public function isAtiva(): bool
    {
        return $this->status === 'ativo';
    }
}
