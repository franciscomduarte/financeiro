<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

class CreateUsuarioAction
{
    public function execute(array $data): User
    {
        return User::create($data);
    }
}
