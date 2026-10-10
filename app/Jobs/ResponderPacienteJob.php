<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Pacientes\RegistrarMensagemPacienteAction;
use App\Models\AssistenteConfiguracao;
use App\Models\Paciente;
use App\Models\PacienteMensagem;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use App\Support\Telefone;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Paciente mandou WhatsApp: o assistente avisa que a equipe vai responder e alerta a equipe.
 * Mensagem fixa (sem IA). Se a clínica ou o assistente já falou com o paciente nas últimas horas, fica quieto.
 */
class ResponderPacienteJob implements ShouldQueue
{
    use Queueable;

    /** Janela em que o assistente não repete a resposta automática (a equipe já está na conversa). */
    public const HORAS_SEM_REPETIR = 12;

    public int $tries = 1; // não repete envio ao paciente

    public function __construct(public readonly string $pacienteId, public readonly string $mensagemId) {}

    public function handle(WhatsAppService $whatsapp, ClinicaAtual $clinicaAtual, RegistrarMensagemPacienteAction $registrar): void
    {
        $ultima = PacienteMensagem::query()->where('paciente_id', $this->pacienteId)->where('enviada', false)
            ->latest('created_at')->orderByDesc('id')->value('id');
        if ($ultima !== $this->mensagemId) {
            return; // chegou mensagem mais nova: o job dela decide
        }

        $config  = AssistenteConfiguracao::query()->select(['id', 'ativo'])->first();
        $clinica = $clinicaAtual->get();
        if (! $config?->ativo || $clinica === null || ! $clinica->whatsappConfigurado()) {
            return;
        }

        Cache::lock("assistente:paciente:{$this->pacienteId}", 60)->block(20, function () use ($whatsapp, $registrar, $clinica): void {
            $jaConversando = PacienteMensagem::query()->where('paciente_id', $this->pacienteId)->where('enviada', true)
                ->where('created_at', '>=', now()->subHours(self::HORAS_SEM_REPETIR))->exists();
            $paciente = Paciente::query()->select(['id', 'nome', 'telefone', 'anonimizado_em'])->find($this->pacienteId);
            $nacional = Telefone::nacional($paciente?->telefone);
            if ($jaConversando || $paciente === null || $paciente->anonimizado_em !== null || $nacional === null) {
                return;
            }

            $primeiroNome = explode(' ', trim($paciente->nome))[0];
            $texto = "Olá, {$primeiroNome}! 😊 Aqui é a assistente virtual da {$clinica->nome}. Recebi sua mensagem e já avisei a nossa equipe: em breve alguém te responde por aqui.";

            if (! $whatsapp->enviarTextoParaTelefone($nacional, $texto)) {
                Log::warning('[Assistente] resposta ao paciente não enviada', ['tenant_id' => $clinica->id, 'paciente_id' => $paciente->id]);

                return;
            }
            $registrar->execute($paciente->id, $texto, true, $whatsapp->ultimoIdMensagem, doAssistente: true);

            // Alerta no WhatsApp da gestão com o que o paciente escreveu
            $recebida = (string) PacienteMensagem::query()->whereKey($this->mensagemId)->value('texto');
            if (filled($clinica->whatsapp_numero)) {
                $aviso = "💬 Paciente {$paciente->nome} mandou mensagem no WhatsApp:\n\"" . mb_strimwidth($recebida, 0, 300, '…') . "\"\n\n"
                    . route('pacientes.index', ['q' => $paciente->nome]);
                $whatsapp->enviarTextoParaTelefone((string) $clinica->whatsapp_numero, $aviso);
            }

            Log::info('[Assistente] paciente atendido e equipe avisada', ['tenant_id' => $clinica->id, 'paciente_id' => $paciente->id]);
        });
    }
}
