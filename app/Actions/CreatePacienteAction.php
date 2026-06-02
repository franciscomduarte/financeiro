<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Paciente;

class CreatePacienteAction
{
    public function execute(array $data): Paciente
    {
        return Paciente::create($data);
    }
}
