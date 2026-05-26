<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

class UpdateUsuarioAction
{
    public function execute(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }
}
