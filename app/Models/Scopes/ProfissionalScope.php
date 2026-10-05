<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Agendamento;
use App\Support\EscopoProfissional;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limita agendamentos, pacientes e registros do prontuário ao profissional logado (perfil Profissional).
 * Models com paciente_id (prontuário) seguem os pacientes que ele atende.
 */
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

        // Pacientes (e o prontuário deles): só os que têm ou tiveram atendimento com o profissional
        $coluna = $model instanceof \App\Models\Paciente ? 'id' : 'paciente_id';

        $builder->whereIn($model->qualifyColumn($coluna), fn ($q) => $q->select('paciente_id')
            ->from('agendamentos')
            ->where('profissional_id', $profissionalId)
            ->whereNotNull('paciente_id'));
    }
}
