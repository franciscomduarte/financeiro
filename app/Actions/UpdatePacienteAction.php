<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Paciente;

class UpdatePacienteAction
{
    public function execute(Paciente $paciente, array $data): Paciente
    {
        $paciente->update($data);

        return $paciente->fresh();
    }
}
