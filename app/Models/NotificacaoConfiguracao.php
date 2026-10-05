<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoNotificacao;
use App\Models\Concerns\BelongsToClinica;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Ajustes da clínica para um tipo automático (canais, antecedência e texto). Sem registro = padrão. */
class NotificacaoConfiguracao extends Model
{
    use BelongsToClinica, HasUuids;

    protected $table = 'notificacao_configuracoes';

    protected $fillable = ['tipo', 'whatsapp', 'email', 'antecedencia_minutos', 'assunto', 'texto'];

    protected $casts = [
        'tipo'                 => TipoNotificacao::class,
        'whatsapp'             => 'boolean',
        'email'                => 'boolean',
        'antecedencia_minutos' => 'integer',
    ];

    public function assuntoEfetivo(): string
    {
        return filled($this->assunto) ? (string) $this->assunto : $this->tipo->assuntoPadrao();
    }

    public function textoEfetivo(): string
    {
        return filled($this->texto) ? (string) $this->texto : $this->tipo->textoPadrao();
    }

    public function antecedenciaEfetiva(): ?int
    {
        return $this->antecedencia_minutos ?? $this->tipo->antecedenciaPadrao();
    }
}
