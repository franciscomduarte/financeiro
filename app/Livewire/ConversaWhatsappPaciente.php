<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Pacientes\EnviarWhatsAppPacienteAction;
use App\Models\Paciente;
use App\Models\PacienteMensagem;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/** Conversa no WhatsApp com o paciente, dentro da ficha (mensagens que não viram lead). */
class ConversaWhatsappPaciente extends Component
{
    use Concerns\MensagemDeErro;

    private const LIMITE = 50;

    #[Locked]
    public string $pacienteId;

    public string $resposta = '';
    public ?string $flashErro = null;

    public function mount(string $pacienteId): void
    {
        // Escopos de clínica e de profissional valem aqui também
        $this->pacienteId = Paciente::query()->select(['id'])->findOrFail($pacienteId)->id;
        $this->marcarComoLidas();
    }

    /** @return Collection<int, PacienteMensagem> da mais antiga para a mais nova */
    #[Computed]
    public function mensagens(): Collection
    {
        return PacienteMensagem::query()
            ->select(['id', 'paciente_id', 'user_id', 'enviada', 'do_assistente', 'texto', 'created_at'])
            ->with('autor:id,name')
            ->where('paciente_id', $this->pacienteId)
            ->latest('created_at')->limit(self::LIMITE)->get()
            ->reverse()->values();
    }

    #[Computed]
    public function paciente(): Paciente
    {
        return Paciente::query()->select(['id', 'nome', 'telefone', 'anonimizado_em'])->findOrFail($this->pacienteId);
    }

    /** Atualização automática enquanto a ficha está aberta. */
    public function atualizar(): void
    {
        unset($this->mensagens);
        $this->marcarComoLidas();
    }

    public function enviar(EnviarWhatsAppPacienteAction $enviar): void
    {
        $this->flashErro = null;
        $this->validate(['resposta' => ['required', 'string', 'max:2000']], ['resposta.required' => 'Escreva a mensagem.']);

        try {
            $enviar->execute($this->pacienteId, $this->resposta);
            $this->resposta = '';
            unset($this->mensagens);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível enviar');
        }
    }

    /** Abrir a ficha conta como leitura (quem só consulta não altera nada). */
    private function marcarComoLidas(): void
    {
        if (app(ClinicaAtual::class)->somenteLeitura()) {
            return;
        }

        PacienteMensagem::query()->where('paciente_id', $this->pacienteId)
            ->where('enviada', false)->whereNull('lida_em')
            ->update(['lida_em' => now()]);
    }

    public function render(): View
    {
        return view('livewire.conversa-whatsapp-paciente', [
            'conectado'   => (bool) app(ClinicaAtual::class)->get()?->whatsappConfigurado(),
            'podeEnviar'  => ! app(ClinicaAtual::class)->somenteLeitura(),
        ]);
    }
}
