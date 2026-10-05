<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\ClinicaNaoDefinidaException;
use App\Models\Clinica;
use App\Models\Scopes\ClinicaScope;
use App\Support\ClinicaAtual;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model pertencente a uma clínica: consultas filtradas pela clínica ativa e tenant_id preenchido ao criar.
 * Para consultas entre clínicas (super admin, webhooks), use withoutGlobalScope(ClinicaScope::class) de forma explícita.
 */
trait BelongsToClinica
{
    public static function bootBelongsToClinica(): void
    {
        static::addGlobalScope(new ClinicaScope());

        static::creating(function (Model $model): void {
            if (! empty($model->tenant_id)) {
                return;
            }

            $clinicaId = app(ClinicaAtual::class)->id()
                ?? throw new ClinicaNaoDefinidaException($model::class);

            $model->tenant_id = $clinicaId;
        });

        // Teste grátis encerrado: nada é criado, alterado ou excluído
        static::saving(fn () => app(ClinicaAtual::class)->garantirEscrita());
        static::deleting(fn () => app(ClinicaAtual::class)->garantirEscrita());
    }

    public function initializeBelongsToClinica(): void
    {
        // Nunca aceitar tenant_id vindo de entrada do usuário
        $this->guarded = array_unique([...$this->guarded, 'tenant_id']);
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class, 'tenant_id');
    }
}
