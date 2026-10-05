<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RoleUsuario;
use App\Models\Clinica;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Inclui um usuário na clínica. Se o e-mail já existe (usuário de outra clínica),
 * apenas vincula à clínica com o papel informado — a senha dele não muda.
 */
class CreateUsuarioAction
{
    /** @return array{user: User, existente: bool} */
    public function execute(array $data, Clinica $clinica, RoleUsuario $papel): array
    {
        app(\App\Support\ClinicaAtual::class)->garantirEscrita();

        return DB::transaction(function () use ($data, $clinica, $papel): array {
            $user      = User::where('email', $data['email'])->lockForUpdate()->first();
            $existente = $user !== null;

            if ($existente && $user->pertenceA($clinica)) {
                throw new RuntimeException('Este usuário já tem acesso a esta clínica.');
            }

            $user ??= User::create($data + ['role' => $papel->value]);
            $user->clinicas()->attach($clinica->id, ['papel' => $papel->value]);

            Log::info('[Usuarios] usuário incluído na clínica', [
                'user_id'    => $user->id,
                'tenant_id'  => $clinica->id,
                'papel'      => $papel->value,
                'existente'  => $existente,
                'por'        => auth()->id(),
            ]);

            return ['user' => $user, 'existente' => $existente];
        });
    }
}
