<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Prontuario\CriarModelosPadraoAction;
use App\Actions\Prontuario\SalvarModeloProntuarioAction;
use App\Enums\TipoModeloProntuario;
use App\Models\ProntuarioModelo;
use App\Support\PreencherModeloProntuario;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

/** Modelos de termos de consentimento e de orientações da clínica. */
class ProntuarioModelos extends Component
{
    use Concerns\MensagemDeErro;

    public bool $modal = false;
    public ?string $editandoId = null;
    public string $tipo = 'termo';
    public string $titulo = '';
    public string $conteudo = '';
    public bool $ativo = true;

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function novo(string $tipo = 'termo'): void
    {
        $this->reset('editandoId', 'titulo', 'conteudo');
        $this->resetValidation();
        $this->tipo  = TipoModeloProntuario::tryFrom($tipo)?->value ?? 'termo';
        $this->ativo = true;
        $this->modal = true;
    }

    public function editar(string $id): void
    {
        $modelo = ProntuarioModelo::query()->select(['id', 'tipo', 'titulo', 'conteudo', 'ativo'])->findOrFail($id);

        $this->resetValidation();
        $this->editandoId = $modelo->id;
        $this->tipo       = $modelo->tipo->value;
        $this->titulo     = $modelo->titulo;
        $this->conteudo   = $modelo->conteudo;
        $this->ativo      = $modelo->ativo;
        $this->modal      = true;
    }

    public function salvar(SalvarModeloProntuarioAction $salvar): void
    {
        $this->flashSucesso = $this->flashErro = null;
        $this->validate([
            'tipo'     => ['required', Rule::enum(TipoModeloProntuario::class)],
            'titulo'   => ['required', 'string', 'max:150'],
            'conteudo' => ['required', 'string', 'min:20', 'max:30000'],
            'ativo'    => ['boolean'],
        ], [
            'titulo.required'   => 'Dê um título ao modelo.',
            'conteudo.required' => 'Escreva o texto do modelo.',
            'conteudo.min'      => 'O texto está curto demais.',
        ]);

        try {
            $salvar->execute($this->editandoId, TipoModeloProntuario::from($this->tipo), $this->titulo, $this->conteudo, $this->ativo);
            $this->modal        = false;
            $this->flashSucesso = 'Modelo salvo.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar o modelo');
        }
    }

    public function usarModelosProntos(CriarModelosPadraoAction $criar): void
    {
        $this->flashSucesso = $this->flashErro = null;

        try {
            $criar->execute();
            $this->flashSucesso = 'Modelos prontos adicionados. Revise os textos e ajuste à sua clínica.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível criar os modelos');
        }
    }

    public function render(): View
    {
        $modelos = ProntuarioModelo::query()
            ->select(['id', 'tipo', 'titulo', 'ativo', 'updated_at'])
            ->orderBy('tipo')->orderBy('titulo')
            ->limit(200)
            ->get()
            ->groupBy(fn (ProntuarioModelo $m) => $m->tipo->value);

        return view('livewire.prontuario-modelos', [
            'modelos' => $modelos,
            'tipos'   => TipoModeloProntuario::cases(),
            'campos'  => PreencherModeloProntuario::CAMPOS,
        ])->layout('layouts.app', ['title' => 'Modelos de termos e orientações']);
    }
}
