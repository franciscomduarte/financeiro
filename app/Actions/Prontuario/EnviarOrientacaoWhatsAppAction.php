<?php

declare(strict_types=1);

namespace App\Actions\Prontuario;

use App\Models\ProntuarioOrientacao;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use RuntimeException;

/** Envia as orientações para o WhatsApp do paciente e marca a data do envio. */
class EnviarOrientacaoWhatsAppAction
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
        private readonly ClinicaAtual $clinicaAtual,
    ) {}

    public function execute(ProntuarioOrientacao $orientacao): void
    {
        $this->clinicaAtual->garantirEscrita();

        $paciente = $orientacao->paciente()->select(['id', 'nome', 'telefone'])->firstOrFail();
        if (! $paciente->telefone) {
            throw new RuntimeException('Cadastre o telefone do paciente para enviar por WhatsApp.');
        }

        $mensagem = "*{$orientacao->titulo}*\n\n{$orientacao->texto}\n\n— " . $this->clinicaAtual->nome();

        if (! $this->whatsApp->enviarTextoParaTelefone($paciente->telefone, $mensagem)) {
            throw new RuntimeException('O WhatsApp não respondeu. Confira a conexão nas configurações da clínica e tente de novo.');
        }
        app(\App\Services\NotificacaoService::class)->registrar(\App\Enums\TipoNotificacao::Orientacao, \App\Enums\CanalNotificacao::WhatsApp, $paciente, (string) $paciente->telefone, (string) $orientacao->titulo, $mensagem, true, $orientacao, $this->whatsApp->ultimoIdMensagem);

        $orientacao->update(['enviada_whatsapp_em' => now()]);
    }
}
