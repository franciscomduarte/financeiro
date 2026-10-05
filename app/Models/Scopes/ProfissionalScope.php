<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Agendamento;
use App\Support\EscopoProfissional;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** Limita agendamentos e pacientes ao profissional logado (perfil Profissional). */
class ProfissionalScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $profissionalId = app(EscopoProfissional::class)->profissionalId();
        if ($profissionalId === null) {
            return;
        }

        if ($model instanceof Agendamento) {
            $builder->where($model->qualifyColumn('profissional_id'), $profissionalId);

            return;
        }

        // Pacientes: só os que têm (ou tiveram) atendimento com o profissional
        $builder->whereIn($model->qualifyColumn('id'), fn ($q) => $q->select('paciente_id')
            ->from('agendamentos')
            ->where('profissional_id', $profissionalId)
            ->whereNotNull('paciente_id'));
    }
}
