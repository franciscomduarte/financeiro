<?php

declare(strict_types=1);

namespace App\Enums;

/** Quem vê o atendimento no prontuário. */
enum VisibilidadeAtendimento: string
{
    case Privado = 'privado';
    case Equipe  = 'equipe';

    public function label(): string
    {
        return match ($this) {
            self::Privado => 'Privado',
            self::Equipe  => 'Equipe',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::Privado => 'Só você e os administradores veem.',
            self::Equipe  => 'Os profissionais da clínica também veem.',
        };
    }
}
