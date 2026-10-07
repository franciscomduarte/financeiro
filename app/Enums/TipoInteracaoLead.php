<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoInteracaoLead: string
{
    case Criado            = 'criado';
    case Nota              = 'nota';
    case Ligacao           = 'ligacao';
    case WhatsAppEnviado   = 'whatsapp_enviado';
    case WhatsAppRecebido  = 'whatsapp_recebido';
    case WhatsAppAssistente = 'whatsapp_assistente';
    case Formulario        = 'formulario';
    case Etapa             = 'etapa';
    case Convertido        = 'convertido';

    public function label(): string
    {
        return match ($this) {
            self::Criado           => 'Lead criado',
            self::Nota             => 'Anotação',
            self::Ligacao          => 'Ligação',
            self::WhatsAppEnviado  => 'WhatsApp enviado',
            self::WhatsAppRecebido => 'Mensagem recebida',
            self::WhatsAppAssistente => 'Resposta do assistente',
            self::Formulario       => 'Formulário preenchido',
            self::Etapa            => 'Mudança de etapa',
            self::Convertido       => 'Virou paciente',
        };
    }

    /** Contato feito pela clínica (conta para "tempo até o primeiro contato"). */
    public function contatoDaClinica(): bool
    {
        return in_array($this, [self::Ligacao, self::WhatsAppEnviado, self::WhatsAppAssistente], true);
    }
}
