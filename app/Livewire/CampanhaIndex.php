<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\StatusCampanha;
use App\Models\Campanha;
use App\Services\Campanhas\RitmoCampanhas;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/** Campanhas de WhatsApp: lista e criação (o envio e a escolha dos pacientes ficam no CampanhaEditor). */
class CampanhaIndex extends Component
{
    use Concerns\MensagemDeErro, WithPagination;

    public ?string $flashErro = null;

    public function nova(): void
    {
        if ($this->somenteLeituraAvisado()) {
            return;
        }

        try {
            $campanha = Campanha::create([
                'nome'     => 'Campanha de ' . now(config('clinica.fuso_horario'))->format('d/m'),
                'mensagem' => "Olá, {primeiro_nome}! 😊\n\n",
                'status'   => StatusCampanha::Rascunho,
                'user_id'  => auth()->id(),
            ]);
            $this->redirectRoute('campanhas.editar', $campanha->id, navigate: true);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível criar a campanha');
        }
    }

    public function render(RitmoCampanhas $ritmo): View
    {
        return view('livewire.campanha-index', [
            'campanhas' => Campanha::query()
                ->select(['id', 'nome', 'status', 'total', 'enviados', 'falhas', 'iniciada_em', 'concluida_em', 'created_at'])
                ->withCount('destinatarios')
                ->latest('created_at')->paginate(20),
            'enviadasHoje' => $ritmo->enviadasHoje(),
            'limite'       => $ritmo->limiteDiario(),
            'regras'       => $ritmo->descricao(),
        ])->layout('layouts.app', ['title' => 'Campanhas']);
    }
}
