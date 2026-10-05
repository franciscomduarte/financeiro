<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Profissional;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Liga o usuário (perfil profissional) ao cadastro do profissional na agenda da clínica ativa.
 * Com $profissionalId nulo, desfaz o vínculo (ex.: mudou de perfil).
 */
class VincularProfissionalAction
{
    public function execute(int $userId, ?string $profissionalId): void
    {
        DB::transaction(function () use ($userId, $profissionalId): void {
            Profissional::query()->where('user_id', $userId)
                ->when($profissionalId, fn ($q) => $q->whereKeyNot($profissionalId))
                ->update(['user_id' => null]);

            if ($profissionalId) {
                // Um profissional da agenda corresponde a um único usuário
                Profissional::query()->whereKey($profissionalId)->lockForUpdate()->firstOrFail()
                    ->forceFill(['user_id' => $userId])->save();
            }
        });

        Log::info('[Usuarios] vínculo com profissional atualizado', [
            'user_id'         => $userId,
            'profissional_id' => $profissionalId,
            'por'             => auth()->id(),
        ]);
    }
}
