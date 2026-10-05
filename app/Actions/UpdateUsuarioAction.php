<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RoleUsuario;
use App\Models\Clinica;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpdateUsuarioAction
{
    /** Atualiza dados do usuário e, se informado, o papel dele na clínica. */
    public function execute(User $user, array $data, ?Clinica $clinica = null, ?RoleUsuario $papel = null): User
    {
        return DB::transaction(function () use ($user, $data, $clinica, $papel): User {
            if ($clinica && $papel) {
                if ($papel !== RoleUsuario::Admin) {
                    self::garantirOutroAdmin($clinica, $user);
                }
                $user->clinicas()->updateExistingPivot($clinica->id, ['papel' => $papel->value]);
            }

            $user->update($data);

            return $user->fresh();
        });
    }

    /** Impede que a clínica fique sem nenhum administrador. */
    public static function garantirOutroAdmin(Clinica $clinica, User $user): void
    {
        $outrosAdmins = $clinica->usuarios()
            ->wherePivot('papel', RoleUsuario::Admin->value)
            ->where('users.id', '!=', $user->id)
            ->where('users.active', true)
            ->exists();

        $ehAdmin = $clinica->usuarios()->whereKey($user->id)->wherePivot('papel', RoleUsuario::Admin->value)->exists();

        if ($ehAdmin && ! $outrosAdmins) {
            throw new RuntimeException('A clínica precisa de pelo menos um administrador ativo.');
        }
    }
}
