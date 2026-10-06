<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\RoleUsuario;
use App\Enums\VisibilidadeAtendimento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** Atendimento privado: só quem atendeu e os administradores enxergam. */
class VisibilidadeAtendimentoScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();
        if ($user === null || $user->role === RoleUsuario::Admin) {
            return;
        }

        $builder->where(fn (Builder $q) => $q
            ->where($model->qualifyColumn('visibilidade'), VisibilidadeAtendimento::Equipe->value)
            ->orWhere($model->qualifyColumn('user_id'), $user->id));
    }
}
