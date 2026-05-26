<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleUsuario: string
{
    case Admin = 'admin';
    case User  = 'user';

    public function label(): string
    {
        return match($this) {
            self::Admin => 'Administrador',
            self::User  => 'Usuário',
        };
    }
}
