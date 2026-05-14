<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoContaConsumo: string
{
    case Agua      = 'agua';
    case Luz       = 'luz';
    case Gas       = 'gas';
    case Telefone  = 'telefone';
    case Internet  = 'internet';
    case Outro     = 'outro';

    public function label(): string
    {
        return match($this) {
            self::Agua     => 'Água',
            self::Luz      => 'Energia Elétrica',
            self::Gas      => 'Gás',
            self::Telefone => 'Telefone',
            self::Internet => 'Internet',
            self::Outro    => 'Outro',
        };
    }

    public function categoria(): string
    {
        return match($this) {
            self::Agua     => 'Água/Esgoto',
            self::Luz      => 'Energia Elétrica',
            self::Gas      => 'Gás',
            self::Telefone => 'Telecomunicações',
            self::Internet => 'Internet',
            self::Outro    => 'Utilidades',
        };
    }

    public function consumoChave(): ?string
    {
        return match($this) {
            self::Agua     => 'm3',
            self::Luz      => 'kwh',
            self::Gas      => 'm3',
            self::Internet => 'gb',
            self::Telefone => 'minutos',
            self::Outro    => null,
        };
    }

    public function consumoLabel(): ?string
    {
        return match($this) {
            self::Agua     => 'Consumo (m³)',
            self::Luz      => 'Consumo (kWh)',
            self::Gas      => 'Consumo (m³)',
            self::Internet => 'Dados (GB)',
            self::Telefone => 'Minutos',
            self::Outro    => null,
        };
    }

    public function cor(): string
    {
        return match($this) {
            self::Agua     => 'blue',
            self::Luz      => 'amber',
            self::Gas      => 'orange',
            self::Telefone => 'violet',
            self::Internet => 'indigo',
            self::Outro    => 'slate',
        };
    }
}
