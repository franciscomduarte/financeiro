<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tipos de notificação ao paciente. Os de agendamento são automáticos e têm texto editável
 * pela clínica; os demais só ficam registrados no histórico quando outras telas enviam.
 */
enum TipoNotificacao: string
{
    case AgendamentoConfirmado = 'agendamento_confirmado';
    case AgendamentoRemarcado  = 'agendamento_remarcado';
    case AgendamentoCancelado  = 'agendamento_cancelado';
    case LembreteVespera       = 'lembrete_vespera';
    case LembreteDia           = 'lembrete_dia';
    case Cobranca              = 'cobranca';
    case LembretePagamento     = 'lembrete_pagamento';
    case Aniversario           = 'aniversario';
    case Retorno               = 'retorno';
    case Sumido                = 'sumido';
    case Pesquisa              = 'pesquisa';
    case Orcamento             = 'orcamento';
    case Orientacao            = 'orientacao';
    case Documento             = 'documento';

    public function label(): string
    {
        return match ($this) {
            self::AgendamentoConfirmado => 'Agendamento confirmado',
            self::AgendamentoRemarcado  => 'Agendamento remarcado',
            self::AgendamentoCancelado  => 'Agendamento cancelado — {clinica}',
            self::LembreteVespera       => 'Lembrete (véspera)',
            self::LembreteDia           => 'Lembrete (no dia)',
            self::Cobranca              => 'Cobrança',
            self::LembretePagamento     => 'Lembrete de pagamento',
            self::Aniversario           => 'Aniversário',
            self::Retorno               => 'Retorno',
            self::Sumido                => 'Convite para voltar',
            self::Pesquisa              => 'Pesquisa de satisfação',
            self::Orcamento             => 'Orçamento',
            self::Orientacao            => 'Orientações',
            self::Documento             => 'Documento',
        };
    }

    /** Automáticos da agenda: a clínica liga/desliga canais e edita o texto. */
    public function configuravel(): bool
    {
        return in_array($this, self::configuraveis(), true);
    }

    /** @return array<int, self> */
    public static function configuraveis(): array
    {
        return [self::AgendamentoConfirmado, self::AgendamentoRemarcado, self::AgendamentoCancelado, self::LembreteVespera, self::LembreteDia];
    }

    public function lembrete(): bool
    {
        return $this === self::LembreteVespera || $this === self::LembreteDia;
    }

    /** Minutos antes do horário em que o lembrete sai. */
    public function antecedenciaPadrao(): ?int
    {
        return match ($this) {
            self::LembreteVespera => 24 * 60,
            self::LembreteDia     => 2 * 60,
            default               => null,
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::AgendamentoConfirmado => 'Sai assim que o horário é marcado.',
            self::AgendamentoRemarcado  => 'Sai quando o horário muda de dia ou hora.',
            self::AgendamentoCancelado  => 'Sai quando o agendamento é cancelado.',
            self::LembreteVespera       => 'Primeiro lembrete, normalmente um dia antes.',
            self::LembreteDia           => 'Último lembrete, poucas horas antes.',
            default                     => '',
        };
    }

    public function assuntoPadrao(): string
    {
        return match ($this) {
            self::AgendamentoConfirmado => 'Agendamento confirmado: {data} às {hora} — {clinica}',
            self::AgendamentoRemarcado  => 'Agendamento remarcado: {data} às {hora} — {clinica}',
            self::AgendamentoCancelado  => 'Agendamento cancelado — {clinica}',
            self::LembreteVespera, self::LembreteDia => 'Lembrete: seu horário é {quando} às {hora} — {clinica}',
            default                     => $this->label(),
        };
    }

    public function textoPadrao(): string
    {
        $detalhes = "✨ Procedimento: *{procedimento}*\n👩‍⚕️ Profissional: *{profissional}*\n📆 Data: *{dia_semana}, {data}*\n🕐 Horário: *{hora}*";

        return match ($this) {
            self::AgendamentoConfirmado => "📅 *Agendamento confirmado — {clinica}*\n\nOlá, *{primeiro_nome}*!\n\nSeu horário está marcado:\n{$detalhes}\n\nSe tiver dúvidas, é só responder esta mensagem. 🙏",
            self::AgendamentoRemarcado  => "🔄 *Agendamento remarcado — {clinica}*\n\nOlá, *{primeiro_nome}*!\n\nSeu novo horário:\n{$detalhes}\n\nSe tiver dúvidas, é só responder esta mensagem. 🙏",
            self::AgendamentoCancelado  => "❌ *Agendamento cancelado — {clinica}*\n\nOlá, *{primeiro_nome}*!\n\nSeu horário de *{procedimento}* em *{data}* às *{hora}* foi cancelado.{motivo}\n\nPara marcar outro horário, é só responder esta mensagem. 🙏",
            self::LembreteVespera       => "⏰ *Lembrete — {clinica}*\n\nOlá, *{primeiro_nome}*! Seu horário é *{quando}*:\n\n{$detalhes}\n\nSe precisar remarcar, avise com antecedência. 🙏",
            self::LembreteDia           => "⏰ *Te esperamos {quando} às {hora} — {clinica}*\n\nOlá, *{primeiro_nome}*! Seu horário de *{procedimento}* com *{profissional}* está chegando.\n\nSe tiver algum imprevisto, responda esta mensagem. 🙏",
            default                     => '',
        };
    }

    /** @return array<string, string> variável => explicação, para a tela de configuração */
    public static function variaveis(): array
    {
        return [
            '{primeiro_nome}' => 'primeiro nome do paciente',
            '{paciente}'      => 'nome completo',
            '{clinica}'       => 'nome da clínica',
            '{procedimento}'  => 'procedimento',
            '{profissional}'  => 'profissional',
            '{data}'          => 'data (10/10/2026)',
            '{dia_semana}'    => 'dia da semana',
            '{hora}'          => 'horário',
            '{quando}'        => '"hoje", "amanhã" ou a data',
            '{motivo}'        => 'motivo do cancelamento (só aparece se houver)',
        ];
    }
}
