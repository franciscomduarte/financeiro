<?php

declare(strict_types=1);

namespace App\Actions\Pacientes;

use App\Models\Paciente;
use App\Models\PacienteMensagem;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use App\Support\Telefone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/** Resposta ao paciente pela ficha: envia pelo WhatsApp da clínica e guarda na conversa. */
class EnviarWhatsAppPacienteAction
{
    public function __construct(
        private readonly ClinicaAtual $clinicaAtual,
        private readonly WhatsAppService $whatsapp,
        private readonly RegistrarMensagemPacienteAction $registrar,
    ) {}

    public function execute(string $pacienteId, string $texto): PacienteMensagem
    {
        $this->clinicaAtual->garantirEscrita();
        $texto = mb_substr(trim($texto), 0, 2000);
        if ($texto === '') {
            throw new InvalidArgumentException('Escreva a mensagem.');
        }
        if (! $this->clinicaAtual->get()?->whatsappConfigurado()) {
            throw new RuntimeException('O WhatsApp da clínica não está conectado. Configure em Dados da clínica.');
        }

        $paciente = Paciente::query()->select(['id', 'telefone', 'anonimizado_em'])->findOrFail($pacienteId);
        $nacional = Telefone::nacional($paciente->telefone);
        if ($paciente->anonimizado_em !== null || $nacional === null) {
            throw new InvalidArgumentException('Este paciente não tem um celular com DDD cadastrado.');
        }

        try {
            $enviado = $this->whatsapp->enviarTextoParaTelefone($nacional, $texto);
        } catch (ConnectionException $e) {
            Log::error('[Pacientes] Evolution fora do ar ao enviar WhatsApp', ['tenant_id' => $this->clinicaAtual->id(), 'paciente_id' => $paciente->id, 'erro' => $e->getMessage()]);

            throw new RuntimeException('O servidor do WhatsApp não respondeu. Tente de novo em instantes.');
        }
        if (! $enviado) {
            throw new RuntimeException('O WhatsApp não aceitou a mensagem. Confira a conexão da clínica e tente de novo.');
        }

        Log::info('[Pacientes] WhatsApp enviado pela ficha', ['tenant_id' => $this->clinicaAtual->id(), 'paciente_id' => $paciente->id, 'user_id' => auth()->id()]);

        return $this->registrar->execute($paciente->id, $texto, true, $this->whatsapp->ultimoIdMensagem, auth()->id());
    }
}
