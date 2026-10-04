<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Exceptions\ClinicaNaoDefinidaException;
use App\Support\ClinicaAtual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** Restringe toda consulta à clínica ativa; sem clínica ativa, falha em vez de devolver dados de todas. */
class ClinicaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $clinicaId = app(ClinicaAtual::class)->id();

        if ($clinicaId === null) {
            throw new ClinicaNaoDefinidaException($model::class);
        }

        $builder->where($model->qualifyColumn('tenant_id'), $clinicaId);
    }
}
