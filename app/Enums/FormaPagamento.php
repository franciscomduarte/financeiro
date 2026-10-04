<?php

declare(strict_types=1);

namespace App\Enums;

enum FormaPagamento: string
{
    case Pix = 'pix';
    case Dinheiro = 'dinheiro';
    case Boleto = 'boleto';
    case Debito = 'debito';
    case Credito1x = 'credito_1x';
    case Credito2x = 'credito_2x';
    case Credito3x = 'credito_3x';
    case Credito4x = 'credito_4x';
    case Credito5x = 'credito_5x';
    case Credito6x = 'credito_6x';
    case Credito7x = 'credito_7x';
    case Credito8x = 'credito_8x';
    case Credito9x = 'credito_9x';
    case Credito10x = 'credito_10x';
    case Credito11x = 'credito_11x';
    case Credito12x = 'credito_12x';
    case AportePessoal = 'aporte_pessoal';

    public function label(): string
    {
        return match ($this) {
            self::Pix           => 'Pix',
            self::Dinheiro      => 'Dinheiro',
            self::Boleto        => 'Boleto',
            self::Debito        => 'Débito',
            self::AportePessoal => 'Aporte pessoal',
            default             => 'Crédito ' . substr($this->value, strlen('credito_')),
        };
    }

    public function modalidade(): string
    {
        return $this->value;
    }

    public function semTaxa(): bool
    {
        return in_array($this, [self::Pix, self::Dinheiro, self::AportePessoal], true);
    }

    public function semImposto(): bool
    {
        return $this === self::AportePessoal;
    }
}
