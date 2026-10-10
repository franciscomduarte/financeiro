<?php

declare(strict_types=1);

namespace App\Actions\NotaFiscal;

use App\Services\FocusNfeService;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cadastra na Focus NFe o aviso automático (gatilho) da clínica: quando a prefeitura autoriza,
 * recusa ou cancela uma nota, a Focus chama o sistema na hora, sem esperar a próxima consulta.
 */
class AtivarAvisoNfseAction
{
    public function __construct(
        private readonly ClinicaAtual $clinicaAtual,
        private readonly FocusNfeService $focus,
    ) {}

    public function execute(): void
    {
        $this->clinicaAtual->garantirEscrita();
        $clinica = $this->clinicaAtual->get();

        $pendencias = $clinica->pendenciasNfse();
        if ($pendencias !== []) {
            throw new RuntimeException('Complete antes os dados da nota fiscal: ' . implode(', ', $pendencias) . '.');
        }

        $token     = $clinica->nfse_webhook_token ?: Str::random(48);
        $resultado = $this->focus->cadastrarGatilho($clinica->nfse_padrao, route('webhook.nfse'), $token);
        if (! $resultado['ok']) {
            throw new RuntimeException('A Focus NFe não aceitou o aviso automático: ' . $resultado['mensagem']);
        }

        $clinica->forceFill([
            'nfse_webhook_token'       => $token,
            'nfse_webhook_em'          => now(),
            'nfse_webhook_homologacao' => (bool) $clinica->nfse_homologacao,
        ])->save();
        $this->clinicaAtual->definir($clinica->fresh());

        Log::info('[NFS-e] aviso automático cadastrado na Focus', ['tenant_id' => $clinica->id, 'user_id' => auth()->id()]);
    }
}
