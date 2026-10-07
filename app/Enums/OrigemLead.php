<?php

declare(strict_types=1);

namespace App\Enums;

/** De onde o lead veio (o formulário público recebe a origem pelo link: ?origem=instagram). */
enum OrigemLead: string
{
    case Instagram = 'instagram';
    case WhatsApp  = 'whatsapp';
    case Site      = 'site';
    case Indicacao = 'indicacao';
    case Google    = 'google';
    case Facebook  = 'facebook';
    case Formulario = 'formulario';
    case Outra     = 'outra';

    public function label(): string
    {
        return match ($this) {
            self::Instagram  => 'Instagram',
            self::WhatsApp   => 'WhatsApp',
            self::Site       => 'Site',
            self::Indicacao  => 'Indicação',
            self::Google     => 'Google',
            self::Facebook   => 'Facebook',
            self::Formulario => 'Formulário',
            self::Outra      => 'Outra',
        };
    }

    /** Origens oferecidas como link do formulário público. @return array<int, self> */
    public static function doFormulario(): array
    {
        return [self::Instagram, self::Site, self::Facebook, self::Google, self::Formulario];
    }
}
