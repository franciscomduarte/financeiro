<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\RoleUsuario;
use App\Models\Profissional;

/**
 * Usuário com perfil Profissional enxerga só a própria agenda e os pacientes que atende.
 * Vale apenas para o usuário logado (telas e API); rotinas e jobs não são afetados.
 */
class EscopoProfissional
{
    /** Id que não existe: profissional sem vínculo não vê nada (em vez de ver tudo). */
    public const NENHUM = '00000000-0000-0000-0000-000000000000';

    private bool $resolvido = false;
    private ?string $profissionalId = null;

    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    /** Profissional do usuário logado, ou null quando não há restrição (outros perfis, rotinas). */
    public function profissionalId(): ?string
    {
        if ($this->resolvido) {
            return $this->profissionalId;
        }

        $user = auth()->user();
        if ($user === null || $this->clinicaAtual->get() === null) {
            return null; // ainda sem usuário/clínica: não memoriza, resolve de novo depois
        }

        $this->resolvido      = true;
        $this->profissionalId = $user->papelNa() === RoleUsuario::Profissional
            ? (Profissional::query()->where('user_id', $user->id)->value('id') ?? self::NENHUM)
            : null;

        return $this->profissionalId;
    }

    public function ativo(): bool
    {
        return $this->profissionalId() !== null;
    }

    public function esquecer(): void
    {
        $this->resolvido      = false;
        $this->profissionalId = null;
    }
}
