<?php

declare(strict_types=1);

namespace App\Actions\Campanhas;

use App\Actions\Pacientes\RegistrarMensagemPacienteAction;
use App\Enums\StatusCampanha;
use App\Enums\StatusDestinatarioCampanha;
use App\Models\Campanha;
use App\Models\CampanhaDestinatario;
use App\Services\Campanhas\RitmoCampanhas;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use App\Support\Telefone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Envia a próxima mensagem da fila de campanhas da clínica ativa (a campanha iniciada primeiro vai antes).
 * Devolve true quando enviou (ou pulou) alguém e a fila deve continuar; false quando é para parar por agora.
 */
class EnviarProximaMensagemCampanhaAction
{
    /** Falhas seguidas que pausam a campanha (provável WhatsApp desconectado). */
    public const FALHAS_PARA_PAUSAR = 3;

    public function __construct(
        private readonly RitmoCampanhas $ritmo,
        private readonly WhatsAppService $whatsapp,
        private readonly ClinicaAtual $clinicaAtual,
        private readonly RegistrarMensagemPacienteAction $registrar,
    ) {}

    public function execute(): bool
    {
        if (! $this->ritmo->dentroDoHorario() || $this->ritmo->limiteAtingido()) {
            return false;
        }
        $clinica = $this->clinicaAtual->get();
        if ($clinica === null || ! $clinica->whatsappConfigurado()) {
            return false;
        }

        $destinatario = CampanhaDestinatario::query()
            ->with(['campanha:id,mensagem,imagem_path,imagem_mime,status', 'paciente:id,nome,telefone,anonimizado_em'])
            ->select(['campanha_destinatarios.id', 'campanha_destinatarios.campanha_id', 'campanha_destinatarios.paciente_id', 'campanha_destinatarios.status'])
            ->join('campanhas', 'campanhas.id', '=', 'campanha_destinatarios.campanha_id')
            ->where('campanhas.status', StatusCampanha::Enviando->value)
            ->where('campanha_destinatarios.status', StatusDestinatarioCampanha::Pendente->value)
            ->orderBy('campanhas.iniciada_em')->orderBy('campanha_destinatarios.created_at')->orderBy('campanha_destinatarios.id')
            ->first();

        if ($destinatario === null) {
            $this->concluirSemPendentes();

            return false;
        }

        $campanha = $destinatario->campanha;
        $paciente = $destinatario->paciente;
        $telefone = Telefone::nacional($paciente?->telefone);
        if ($paciente === null || $paciente->anonimizado_em !== null || $telefone === null) {
            $this->finalizar($destinatario, StatusDestinatarioCampanha::Ignorado, 'Paciente sem celular válido.');

            return true;
        }

        $texto = $campanha->mensagemPara((string) $paciente->nome);
        try {
            $ok = $campanha->imagem_path
                ? $this->whatsapp->enviarImagemParaTelefone($telefone, base64_encode((string) Storage::disk('local')->get($campanha->imagem_path)), (string) $campanha->imagem_mime, $texto)
                : $this->whatsapp->enviarTextoParaTelefone($telefone, $texto);
        } catch (Throwable $e) {
            Log::error('[Campanhas] erro no envio', ['campanha_id' => $campanha->id, 'erro' => $e->getMessage()]);
            $ok = false;
        }

        if (! $ok) {
            $this->finalizar($destinatario, StatusDestinatarioCampanha::Falhou, 'O WhatsApp não aceitou a mensagem (número sem WhatsApp ou conexão fora do ar).');
            $this->pausarSeFalhasSeguidas($campanha);

            return true;
        }

        $mensagemId = $this->whatsapp->ultimoIdMensagem;
        $this->finalizar($destinatario, StatusDestinatarioCampanha::Enviado, null, $mensagemId);
        Cache::forget($this->chaveFalhas($campanha->id));
        try {
            $this->registrar->execute($paciente->id, $texto, true, $mensagemId); // fica na conversa da ficha
        } catch (Throwable $e) {
            Log::warning('[Campanhas] mensagem enviada mas não registrada na ficha', ['paciente_id' => $paciente->id, 'erro' => $e->getMessage()]);
        }

        return true;
    }

    private function finalizar(CampanhaDestinatario $d, StatusDestinatarioCampanha $status, ?string $erro, ?string $mensagemId = null): void
    {
        DB::transaction(function () use ($d, $status, $erro, $mensagemId): void {
            CampanhaDestinatario::query()->whereKey($d->id)->update([
                'status' => $status->value, 'erro' => $erro, 'mensagem_id' => $mensagemId,
                'enviado_em' => $status === StatusDestinatarioCampanha::Enviado ? now() : null, 'updated_at' => now(),
            ]);
            $coluna = match ($status) {
                StatusDestinatarioCampanha::Enviado => 'enviados',
                StatusDestinatarioCampanha::Pendente => null,
                default => 'falhas',
            };
            if ($coluna !== null) {
                Campanha::query()->whereKey($d->campanha_id)->increment($coluna);
            }
            $this->concluirSeTerminou($d->campanha_id);
        });
    }

    private function concluirSeTerminou(string $campanhaId): void
    {
        $pendentes = CampanhaDestinatario::query()->where('campanha_id', $campanhaId)
            ->where('status', StatusDestinatarioCampanha::Pendente->value)->exists();
        if (! $pendentes) {
            Campanha::query()->whereKey($campanhaId)->where('status', StatusCampanha::Enviando->value)
                ->update(['status' => StatusCampanha::Concluida->value, 'concluida_em' => now(), 'updated_at' => now()]);
        }
    }

    private function concluirSemPendentes(): void
    {
        Campanha::query()->where('status', StatusCampanha::Enviando->value)
            ->whereDoesntHave('destinatarios', fn ($q) => $q->where('status', StatusDestinatarioCampanha::Pendente->value))
            ->update(['status' => StatusCampanha::Concluida->value, 'concluida_em' => now(), 'updated_at' => now()]);
    }

    private function pausarSeFalhasSeguidas(Campanha $campanha): void
    {
        $chave = $this->chaveFalhas($campanha->id);
        Cache::add($chave, 0, now()->addHours(6));
        if (Cache::increment($chave) >= self::FALHAS_PARA_PAUSAR) {
            Campanha::query()->whereKey($campanha->id)->where('status', StatusCampanha::Enviando->value)
                ->update(['status' => StatusCampanha::Pausada->value, 'updated_at' => now()]);
            Cache::forget($chave);
            Log::warning('[Campanhas] pausada após falhas seguidas', ['campanha_id' => $campanha->id]);
        }
    }

    private function chaveFalhas(string $campanhaId): string
    {
        return "campanhas:falhas:{$campanhaId}";
    }
}
