<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Atendimento\GarantirFichasPadraoAction;
use App\Actions\Atendimento\SalvarFichaModeloAction;
use App\Enums\TipoCampoFicha;
use App\Models\FichaModelo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/** Fichas do atendimento (Anamnese, Capilar...): a clínica edita perguntas, ordem e quais aparecem. */
class FichaModelos extends Component
{
    use Concerns\MensagemDeErro;

    public bool $modal = false;
    public ?string $editandoId = null;
    public string $nome = '';
    public string $descricao = '';
    public bool $ativo = true;
    /** @var array<int, array{id: string, tipo: string, rotulo: string, opcoes: string}> */
    public array $campos = [];

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(GarantirFichasPadraoAction $padrao): void
    {
        $padrao->execute();
    }

    #[Computed]
    public function modelos(): Collection
    {
        return FichaModelo::query()->select(['id', 'nome', 'descricao', 'campos', 'ordem', 'ativo'])->orderBy('ordem')->orderBy('nome')->get();
    }

    public function novo(): void
    {
        $this->resetValidation();
        $this->reset('editandoId', 'nome', 'descricao');
        $this->ativo  = true;
        $this->campos = [$this->campoVazio(TipoCampoFicha::TextoRico)];
        $this->modal  = true;
    }

    public function editar(string $id): void
    {
        $m = FichaModelo::query()->findOrFail($id);
        $this->resetValidation();
        $this->editandoId = $m->id;
        $this->nome       = $m->nome;
        $this->descricao  = (string) $m->descricao;
        $this->ativo      = $m->ativo;
        $this->campos     = array_map(fn (array $c) => [
            'id' => (string) $c['id'], 'tipo' => (string) $c['tipo'], 'rotulo' => (string) $c['rotulo'], 'opcoes' => implode("\n", $c['opcoes'] ?? []),
        ], $m->campos);
        $this->modal = true;
    }

    public function adicionarCampo(): void
    {
        $this->campos[] = $this->campoVazio(TipoCampoFicha::Texto);
    }

    public function removerCampo(int $i): void
    {
        unset($this->campos[$i]);
        $this->campos = array_values($this->campos);
    }

    public function moverCampo(int $i, int $direcao): void
    {
        $alvo = $i + ($direcao < 0 ? -1 : 1);
        if (isset($this->campos[$i], $this->campos[$alvo])) {
            [$this->campos[$i], $this->campos[$alvo]] = [$this->campos[$alvo], $this->campos[$i]];
        }
    }

    public function salvar(SalvarFichaModeloAction $salvar): void
    {
        $this->validate([
            'nome'            => 'required|string|max:100',
            'descricao'       => 'nullable|string|max:255',
            'campos'          => 'required|array|min:1|max:' . SalvarFichaModeloAction::MAX_CAMPOS,
            'campos.*.rotulo' => 'required|string|max:150',
        ], [
            'nome.required'            => 'Dê um nome para a ficha.',
            'campos.required'          => 'Adicione pelo menos uma pergunta.',
            'campos.*.rotulo.required' => 'Escreva a pergunta.',
        ]);

        try {
            $salvar->execute($this->editandoId, ['nome' => $this->nome, 'descricao' => $this->descricao, 'ativo' => $this->ativo, 'campos' => $this->campos]);
            $this->modal        = false;
            $this->flashSucesso = 'Ficha "' . trim($this->nome) . '" salva.';
            unset($this->modelos);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar');
        }
    }

    public function mover(string $id, int $direcao, SalvarFichaModeloAction $salvar): void
    {
        try {
            $salvar->mover($id, $direcao);
            unset($this->modelos);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível reordenar');
        }
    }

    /** @return array{id: string, tipo: string, rotulo: string, opcoes: string} */
    private function campoVazio(TipoCampoFicha $tipo): array
    {
        return ['id' => '', 'tipo' => $tipo->value, 'rotulo' => '', 'opcoes' => ''];
    }

    public function render(): View
    {
        return view('livewire.ficha-modelos', ['tipos' => TipoCampoFicha::cases()]);
    }
}
