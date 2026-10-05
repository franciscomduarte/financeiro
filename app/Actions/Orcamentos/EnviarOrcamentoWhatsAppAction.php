<?php

declare(strict_types=1);

namespace App\Actions\Orcamentos;

use App\Models\Orcamento;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use RuntimeException;

/** Manda o resumo do orçamento para o WhatsApp do paciente e marca a data do envio. */
class EnviarOrcamentoWhatsAppAction
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
        private readonly ClinicaAtual $clinicaAtual,
    ) {}

    public function execute(string $orcamentoId): void
    {
        $this->clinicaAtual->garantirEscrita();

        $orcamento = Orcamento::query()->with(['paciente:id,nome,telefone', 'itens'])->findOrFail($orcamentoId);
        $telefone  = $orcamento->paciente?->telefone;
        if (! $telefone) {
            throw new RuntimeException('Cadastre o telefone do paciente para enviar por WhatsApp.');
        }

        if (! $this->whatsApp->enviarTextoParaTelefone($telefone, self::mensagem($orcamento, $this->clinicaAtual->nome()))) {
            throw new RuntimeException('O WhatsApp não respondeu. Confira a conexão nas configurações da clínica e tente de novo.');
        }

        $orcamento->update(['enviado_em' => now()]);
    }

    public static function mensagem(Orcamento $orcamento, string $clinica): string
    {
        $brl   = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
        $nome  = explode(' ', (string) $orcamento->paciente?->nome)[0];
        $linhas = $orcamento->itens->map(fn ($i) => "• {$i->quantidade}× {$i->descricao}: {$brl($i->subtotal)}")->implode("\n");

        $msg = "Olá, {$nome}! Segue o seu orçamento {$orcamento->codigo()} da {$clinica}:\n\n{$linhas}\n";
        if ((float) $orcamento->desconto > 0) {
            $msg .= "\nDesconto: {$brl($orcamento->desconto)}";
        }
        $msg .= "\n*Total: {$brl($orcamento->total)}*";
        if ($orcamento->forma_pagamento) {
            $msg .= " ({$orcamento->forma_pagamento->label()})";
        }
        $msg .= "\nVálido até " . $orcamento->validade->format('d/m/Y') . '.';

        return $msg . "\n\nQualquer dúvida, é só responder esta mensagem. 😊";
    }
}
