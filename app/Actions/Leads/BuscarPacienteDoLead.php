<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Models\Paciente;

/** Paciente que já tem o telefone (comparado pela chave: DDD + 8 dígitos) ou o e-mail do lead. */
final class BuscarPacienteDoLead
{
    public static function porContato(?string $chaveTelefone, ?string $email): ?Paciente
    {
        if ($chaveTelefone === null && $email === null) {
            return null;
        }

        return Paciente::query()->select(['id', 'nome', 'telefone', 'email'])
            ->whereNull('anonimizado_em')
            ->where(function ($q) use ($chaveTelefone, $email): void {
                if ($chaveTelefone !== null) {
                    // Mesmo DDD e mesmos 8 últimos dígitos, com ou sem 55 e com ou sem o 9
                    $q->whereRaw("right(regexp_replace(coalesce(telefone, ''), '\\D', '', 'g'), 8) = ?", [substr($chaveTelefone, 2)])
                        ->whereRaw("regexp_replace(regexp_replace(coalesce(telefone, ''), '\\D', '', 'g'), '^55', '') like ?", [substr($chaveTelefone, 0, 2) . '%']);
                }
                if ($email !== null) {
                    $q->orWhereRaw('lower(email) = ?', [$email]);
                }
            })
            ->first();
    }
}
