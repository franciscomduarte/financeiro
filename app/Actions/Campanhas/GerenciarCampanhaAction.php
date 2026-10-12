<?php

declare(strict_types=1);

namespace App\Actions\Campanhas;

use App\Enums\StatusCampanha;
use App\Enums\StatusDestinatarioCampanha;
use App\Jobs\EnviarCampanhasJob;
use App\Models\Campanha;
use App\Models\CampanhaDestinatario;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Iniciar, pausar, retomar e cancelar o envio de uma campanha. */
class GerenciarCampanhaAction
{
    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    public function iniciar(Campanha $campanha): void
    {
        $this->clinicaAtual->garantirEscrita();
        if (! $this->clinicaAtual->get()?->whatsappConfigurado()) {
            throw new RuntimeException('Conecte o WhatsApp da clínica (Dados da clínica › Integrações) antes de iniciar a campanha.');
        }

        DB::transaction(function () use ($campanha): void {
            $campanha = Campanha::query()->lockForUpdate()->findOrFail($campanha->id);
            if ($campanha->status !== StatusCampanha::Rascunho) {
                throw new RuntimeException('Esta campanha já foi iniciada.');
            }
            if (blank($campanha->mensagem)) {
                throw new RuntimeException('Escreva a mensagem antes de iniciar.');
            }
            $total = CampanhaDestinatario::query()->where('campanha_id', $campanha->id)->count();
            if ($total === 0) {
                throw new RuntimeException('Escolha pelo menos um paciente.');
            }
            $campanha->update(['status' => StatusCampanha::Enviando, 'total' => $total, 'iniciada_em' => now()]);
        });

        EnviarCampanhasJob::iniciarCadeia();
    }

    public function pausar(Campanha $campanha): void
    {
        $this->trocar($campanha, StatusCampanha::Enviando, StatusCampanha::Pausada, 'Só dá para pausar uma campanha que está enviando.');
    }

    public function retomar(Campanha $campanha): void
    {
        if (! $this->clinicaAtual->get()?->whatsappConfigurado()) {
            throw new RuntimeException('Conecte o WhatsApp da clínica antes de retomar a campanha.');
        }
        $this->trocar($campanha, StatusCampanha::Pausada, StatusCampanha::Enviando, 'Só dá para retomar uma campanha pausada.');
        EnviarCampanhasJob::iniciarCadeia();
    }

    public function cancelar(Campanha $campanha): void
    {
        $this->clinicaAtual->garantirEscrita();
        DB::transaction(function () use ($campanha): void {
            $atualizadas = Campanha::query()->whereKey($campanha->id)
                ->whereIn('status', [StatusCampanha::Enviando->value, StatusCampanha::Pausada->value])
                ->update(['status' => StatusCampanha::Cancelada->value, 'concluida_em' => now(), 'updated_at' => now()]);
            if ($atualizadas === 0) {
                throw new RuntimeException('Só dá para cancelar uma campanha enviando ou pausada.');
            }
            CampanhaDestinatario::query()->where('campanha_id', $campanha->id)
                ->where('status', StatusDestinatarioCampanha::Pendente->value)
                ->update(['status' => StatusDestinatarioCampanha::Ignorado->value, 'erro' => 'Campanha cancelada.', 'updated_at' => now()]);
        });
    }

    private function trocar(Campanha $campanha, StatusCampanha $de, StatusCampanha $para, string $erro): void
    {
        $this->clinicaAtual->garantirEscrita();
        $atualizadas = Campanha::query()->whereKey($campanha->id)->where('status', $de->value)
            ->update(['status' => $para->value, 'updated_at' => now()]);
        if ($atualizadas === 0) {
            throw new RuntimeException($erro);
        }
    }
}
