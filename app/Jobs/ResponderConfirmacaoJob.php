<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Pacientes\RegistrarMensagemPacienteAction;
use App\Models\Agendamento;
use App\Models\Scopes\ProfissionalScope;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use App\Support\Telefone;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Agradece a confirmação do paciente ou, quando ele não pode vir, responde e avisa a equipe para remarcar. */
class ResponderConfirmacaoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1; // não repete envio ao paciente

    public function __construct(public readonly string $agendamentoId, public readonly bool $confirmou) {}

    public function handle(WhatsAppService $whatsapp, ClinicaAtual $clinicaAtual, RegistrarMensagemPacienteAction $registrar): void
    {
        $clinica = $clinicaAtual->get();
        $ag = Agendamento::query()->withoutGlobalScope(ProfissionalScope::class)
            ->with(['paciente:id,nome,telefone,anonimizado_em', 'procedimento:id,nome', 'profissional:id,nome'])
            ->select(['id', 'paciente_id', 'procedimento_id', 'profissional_id', 'inicio_em', 'resposta_confirmacao'])
            ->find($this->agendamentoId);
        $paciente = $ag?->paciente;
        if ($ag === null || $clinica === null || ! $clinica->whatsappConfigurado() || $paciente === null || $paciente->anonimizado_em !== null) {
            return;
        }

        $inicio = $ag->inicio_em->timezone((string) config('clinica.fuso_horario'));
        $quando = $inicio->isSameDay(now(config('clinica.fuso_horario'))->addDay()) ? 'amanhã' : 'dia ' . $inicio->format('d/m');
        $primeiroNome = explode(' ', trim($paciente->nome))[0];
        $procedimento = $ag->procedimento?->nome ? " de {$ag->procedimento->nome}" : '';

        $texto = $this->confirmou
            ? "Obrigada, {$primeiroNome}! ✅ Seu horário{$procedimento} {$quando} às {$inicio->format('H:i')} está confirmado. Até lá! 😊"
            : "Tudo bem, {$primeiroNome}! Já avisei a nossa equipe, que vai falar com você por aqui para remarcar. 🙏";

        $nacional = Telefone::nacional($paciente->telefone);
        if ($nacional !== null && $whatsapp->enviarTextoParaTelefone($nacional, $texto)) {
            $registrar->execute($paciente->id, $texto, true, $whatsapp->ultimoIdMensagem, doAssistente: true);
        } else {
            Log::warning('[Agenda] resposta da confirmação não enviada', ['tenant_id' => $clinica->id, 'agendamento_id' => $ag->id]);
        }

        if (! $this->confirmou && filled($clinica->whatsapp_numero)) {
            $aviso = "⚠️ {$paciente->nome} respondeu ao pedido de confirmação e não vai poder vir {$quando} ({$inicio->format('d/m')}) às {$inicio->format('H:i')}"
                . ($ag->procedimento?->nome ? " — {$ag->procedimento->nome}" : '')
                . ($ag->profissional?->nome ? " com {$ag->profissional->nome}" : '') . ".\n"
                . "Resposta: \"" . mb_strimwidth((string) $ag->resposta_confirmacao, 0, 300, '…') . "\"\n\n"
                . "Fale com o paciente para remarcar: " . route('agenda.index');
            $whatsapp->enviarTextoParaTelefone((string) $clinica->whatsapp_numero, $aviso);
        }
    }
}
