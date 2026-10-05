<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Paciente;
use App\Models\PacienteAcesso;

/** Histórico de acessos aos dados de um paciente (mais recentes primeiro). */
class ConsultarAcessosPacienteAction
{
    /** @return array<int, array{quando: string, quem: string, acao: string}> */
    public function execute(Paciente $paciente, int $limite = 20): array
    {
        return PacienteAcesso::query()
            ->select(['id', 'user_id', 'acao', 'created_at'])
            ->with('user:id,name')
            ->where('paciente_id', $paciente->id)
            ->latest('created_at')
            ->limit($limite)
            ->get()
            ->map(fn (PacienteAcesso $a) => [
                'quando' => $a->created_at->toIso8601String(),
                'quem'   => $a->user?->name ?? 'sistema',
                'acao'   => $a->acao->label(),
            ])->all();
    }
}
