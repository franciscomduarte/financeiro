<?php

declare(strict_types=1);

namespace App\Enums;

/** Registro de acesso aos dados do paciente (LGPD). */
enum AcaoAcessoPaciente: string
{
    case Visualizou  = 'visualizou';
    case Editou      = 'editou';
    case Exportou    = 'exportou';
    case Anonimizou  = 'anonimizou';
    case AbriuProntuario = 'abriu_prontuario';

    public function label(): string
    {
        return match ($this) {
            self::Visualizou => 'Abriu a ficha',
            self::Editou     => 'Alterou os dados',
            self::Exportou   => 'Exportou os dados',
            self::Anonimizou => 'Anonimizou o paciente',
            self::AbriuProntuario => 'Abriu o prontuário',
        };
    }
}
