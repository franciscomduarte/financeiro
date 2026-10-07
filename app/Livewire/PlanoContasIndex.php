<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\GrupoPlanoContas;
use App\Enums\TipoTransacao;
use App\Models\PlanoConta;
use App\Models\Transacao;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/** Plano de contas: categorias de receitas e despesas, organizadas nos grupos da DRE. */
class PlanoContasIndex extends Component
{
    use Concerns\MensagemDeErro;

    #[Url(except: 'saida')]
    public string $tipo = 'saida';

    public bool $modal         = false;
    public ?string $editandoId = null;
    public string $nome        = '';
    public string $grupo       = '';

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        $this->tipo = TipoTransacao::tryFrom($this->tipo)?->value ?? 'saida';
        PlanoConta::garantirPadroes();
    }

    /** @return Collection<string, Collection<int, PlanoConta>> por grupo, na ordem da DRE */
    #[Computed]
    public function grupos(): Collection
    {
        $contas = PlanoConta::query()->where('tipo', $this->tipo)
            ->select(['id', 'nome', 'grupo', 'tipo', 'ativa', 'ordem'])
            ->orderBy('ordem')->orderBy('nome')->limit(300)->get()
            ->groupBy(fn (PlanoConta $c) => $c->grupo->value);

        return collect(GrupoPlanoContas::doTipo(TipoTransacao::from($this->tipo)))
            ->mapWithKeys(fn (GrupoPlanoContas $g) => [$g->value => $contas->get($g->value, collect())]);
    }

    /** @return Collection<string, int> conta_id => lançamentos */
    #[Computed]
    public function usos(): Collection
    {
        return Transacao::query()->where('tipo', $this->tipo)->whereNotNull('categoria_id')
            ->groupBy('categoria_id')->selectRaw('categoria_id, COUNT(*) AS total')
            ->pluck('total', 'categoria_id')->map(fn ($v) => (int) $v);
    }

    public function nova(string $grupo): void
    {
        $this->resetErrorBag();
        $this->editandoId = null;
        $this->nome       = '';
        $this->grupo      = GrupoPlanoContas::from($grupo)->value;
        $this->modal      = true;
    }

    public function editar(string $id): void
    {
        $c = PlanoConta::query()->findOrFail($id);
        $this->resetErrorBag();
        $this->editandoId = $c->id;
        $this->nome       = $c->nome;
        $this->grupo      = $c->grupo->value;
        $this->modal      = true;
    }

    public function salvar(): void
    {
        $tiposDoGrupo = array_map(fn (GrupoPlanoContas $g) => $g->value, GrupoPlanoContas::doTipo(TipoTransacao::from($this->tipo)));
        $this->validate([
            'nome'  => ['required', 'string', 'max:100',
                Rule::unique('plano_contas', 'nome')->where('tenant_id', app(ClinicaAtual::class)->id())->where('tipo', $this->tipo)->ignore($this->editandoId)],
            'grupo' => ['required', Rule::in($tiposDoGrupo)],
        ], ['nome.required' => 'Dê um nome à categoria.', 'nome.unique' => 'Já existe uma categoria com esse nome.']);

        try {
            DB::transaction(function (): void {
                $nome = trim($this->nome);
                if ($this->editandoId) {
                    $conta = PlanoConta::query()->lockForUpdate()->findOrFail($this->editandoId);
                    $conta->update(['nome' => $nome, 'grupo' => $this->grupo]);
                    // O nome também fica gravado nos lançamentos (telas e relatórios antigos)
                    Transacao::query()->where('categoria_id', $conta->id)->update(['categoria' => $nome]);
                } else {
                    PlanoConta::query()->create(['nome' => $nome, 'grupo' => $this->grupo, 'tipo' => $this->tipo, 'ativa' => true,
                        'ordem' => (int) PlanoConta::query()->where('grupo', $this->grupo)->max('ordem') + 10]);
                }
            });
            $this->flashSucesso = $this->editandoId ? 'Categoria atualizada.' : 'Categoria criada.';
            $this->modal = false;
            unset($this->grupos);
        } catch (Throwable $e) {
            $this->addError('nome', $this->mensagemDeErro($e, 'Não foi possível salvar'));
        }
    }

    public function alternar(string $id): void
    {
        try {
            $c = PlanoConta::query()->findOrFail($id);
            $c->update(['ativa' => ! $c->ativa]);
            $this->flashSucesso = $c->ativa ? 'Categoria reativada.' : 'Categoria desativada. Os lançamentos dela continuam nos relatórios.';
            unset($this->grupos);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível alterar');
        }
    }

    public function excluir(string $id): void
    {
        try {
            DB::transaction(function () use ($id): void {
                $c = PlanoConta::query()->lockForUpdate()->findOrFail($id);
                if (Transacao::query()->where('categoria_id', $c->id)->exists()) {
                    throw new \RuntimeException('Esta categoria tem lançamentos. Desative em vez de excluir.');
                }
                $c->delete();
            });
            $this->flashSucesso = 'Categoria excluída.';
            unset($this->grupos);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível excluir');
        }
    }

    public function render(): View
    {
        return view('livewire.plano-contas-index', [
            'gruposDoTipo' => GrupoPlanoContas::doTipo(TipoTransacao::from($this->tipo)),
        ])->layout('layouts.app', ['title' => 'Plano de contas']);
    }
}
