<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Configuração do assistente virtual de WhatsApp (uma por clínica). */
class AssistenteConfiguracao extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'assistente_configuracoes';

    protected $fillable = [
        'ativo', 'nome', 'instrucoes', 'informar_precos', 'pode_agendar', 'procedimento_avaliacao_id', 'profissional_id',
        'limite_respostas_mes', 'respostas_mes', 'mes_referencia',
    ];

    protected $casts = [
        'ativo'                => 'boolean',
        'informar_precos'      => 'boolean',
        'pode_agendar'         => 'boolean',
        'limite_respostas_mes' => 'integer',
        'respostas_mes'        => 'integer',
        'mes_referencia'       => 'date',
    ];

    /** Configuração da clínica ativa (cria desligada na primeira vez). */
    public static function atual(): self
    {
        return self::query()->firstOrCreate([], ['ativo' => false, 'nome' => 'Assistente']);
    }

    public function procedimentoAvaliacao(): BelongsTo
    {
        return $this->belongsTo(Procedimento::class, 'procedimento_avaliacao_id');
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }

    /** Respostas usadas no mês corrente (o contador zera ao virar o mês). */
    public function respostasNoMes(): int
    {
        return $this->mes_referencia?->isSameMonth(now()) ? $this->respostas_mes : 0;
    }

    public function limiteAtingido(): bool
    {
        return $this->respostasNoMes() >= $this->limite_respostas_mes;
    }

    public function podeAgendar(): bool
    {
        return $this->pode_agendar && $this->procedimento_avaliacao_id !== null;
    }

    public function contarResposta(): void
    {
        $doMes = $this->mes_referencia?->isSameMonth(now());
        $this->forceFill([
            'respostas_mes'  => $doMes ? $this->respostas_mes + 1 : 1,
            'mes_referencia' => now()->startOfMonth()->toDateString(),
        ])->save();
    }
}
