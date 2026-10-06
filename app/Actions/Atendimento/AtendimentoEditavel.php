<?php

declare(strict_types=1);

namespace App\Actions\Atendimento;

use App\Enums\RoleUsuario;
use App\Models\Atendimento;
use App\Support\ClinicaAtual;
use RuntimeException;

/** Só quem abriu o atendimento (ou um administrador) altera, e só enquanto está em andamento. */
trait AtendimentoEditavel
{
    private function atendimentoEditavel(string $id, bool $travar = false, bool $exigirAberto = true): Atendimento
    {
        app(ClinicaAtual::class)->garantirEscrita();

        $atendimento = Atendimento::query()->when($travar, fn ($q) => $q->lockForUpdate())->findOrFail($id);

        if ($exigirAberto && ! $atendimento->emAndamento()) {
            throw new RuntimeException('Este atendimento já foi finalizado e não pode mais ser alterado. Para corrigir, registre uma evolução no prontuário.');
        }
        $user = auth()->user();
        if ($user !== null && $user->role !== RoleUsuario::Admin && $atendimento->user_id !== $user->id) {
            throw new RuntimeException('Só quem iniciou o atendimento pode alterá-lo.');
        }

        return $atendimento;
    }
}
