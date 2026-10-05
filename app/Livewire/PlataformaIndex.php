<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\AlterarSituacaoClinicaAction;
use App\Enums\RoleUsuario;
use App\Enums\StatusClinica;
use App\Models\Clinica;
use App\Services\IndicadoresPlataformaService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/** Painel do dono da plataforma: clínicas, indicadores e ações (rota com middleware super-admin). */
class PlataformaIndex extends Component
{
    use Concerns\MensagemDeErro, WithPagination;

    #[Url(as: 'q', history: true)]
    public string $busca = '';

    #[Url(history: true)]
    public string $situacao = '';

    #[Url(history: true)]
    public string $ordem = 'recentes';

    public ?string $clinicaAbertaId = null;
    public int $diasExtensao = 7;
    public string $motivoBloqueio = '';

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function updated(string $campo): void
    {
        if (in_array($campo, ['busca', 'situacao', 'ordem'], true)) {
            $this->resetPage();
        }
    }

    public function limparFiltros(): void
    {
        $this->reset('busca', 'situacao', 'ordem');
        $this->resetPage();
    }

    public function abrir(string $id): void
    {
        $this->clinicaAbertaId = Clinica::query()->whereKey($id)->value('id');
        $this->diasExtensao    = 7;
        $this->motivoBloqueio  = '';
        $this->resetErrorBag();
    }

    public function fechar(): void
    {
        $this->clinicaAbertaId = null;
    }

    public function ativar(AlterarSituacaoClinicaAction $action): void
    {
        $this->executar(fn (Clinica $c) => $action->ativar($c), 'Assinatura ativada. A clínica já pode usar tudo.');
    }

    public function bloquear(AlterarSituacaoClinicaAction $action): void
    {
        $this->validate(['motivoBloqueio' => 'nullable|string|max:300']);
        $this->executar(fn (Clinica $c) => $action->bloquear($c, trim($this->motivoBloqueio) ?: null), 'Clínica bloqueada.');
    }

    public function desbloquear(AlterarSituacaoClinicaAction $action): void
    {
        $this->executar(fn (Clinica $c) => $action->desbloquear($c), 'Clínica desbloqueada.');
    }

    public function estenderTeste(AlterarSituacaoClinicaAction $action): void
    {
        $this->validate(
            ['diasExtensao' => 'required|integer|min:1|max:' . AlterarSituacaoClinicaAction::MAX_DIAS_EXTENSAO],
            ['diasExtensao.max' => 'Estenda no máximo ' . AlterarSituacaoClinicaAction::MAX_DIAS_EXTENSAO . ' dias por vez.'],
        );
        $this->executar(
            fn (Clinica $c) => $action->estenderTeste($c, $this->diasExtensao),
            "Teste estendido em {$this->diasExtensao} dia(s).",
        );
    }

    private function executar(callable $acao, string $sucesso): void
    {
        $this->flashSucesso = $this->flashErro = null;

        try {
            $acao(Clinica::query()->findOrFail($this->clinicaAbertaId));
            $this->flashSucesso = $sucesso;
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível alterar a clínica');
        }
    }

    public function render(IndicadoresPlataformaService $indicadores): View
    {
        $admins = fn ($q) => $q->select(['users.id', 'users.name', 'users.email'])
            ->wherePivot('papel', RoleUsuario::Admin->value)
            ->orderBy('clinica_user.created_at');

        $clinicas = Clinica::query()
            ->select(['id', 'nome', 'status', 'teste_ate', 'telefone', 'email_contato', 'created_at', 'ultimo_acesso_em', 'ativada_em'])
            ->with(['usuarios' => $admins])
            ->when(trim($this->busca) !== '', function ($q): void {
                $termo = '%' . str_replace(['%', '_'], ['\\%', '\\_'], trim($this->busca)) . '%';
                $q->where(fn ($w) => $w->where('nome', 'ilike', $termo)
                    ->orWhere('email_contato', 'ilike', $termo)
                    ->orWhereHas('usuarios', fn ($u) => $u->where('users.email', 'ilike', $termo)));
            })
            ->when(StatusClinica::tryFrom($this->situacao), fn ($q, StatusClinica $s) => $q->where('status', $s->value))
            ->when($this->ordem === 'acesso', fn ($q) => $q->orderByRaw('ultimo_acesso_em desc nulls last'))
            ->when($this->ordem === 'teste', fn ($q) => $q->orderByRaw('teste_ate asc nulls last'))
            ->when($this->ordem === 'nome', fn ($q) => $q->orderBy('nome'))
            ->orderByDesc('created_at')
            ->paginate(20);

        $aberta = $this->clinicaAbertaId
            ? Clinica::query()
                ->with([
                    'usuarios' => fn ($q) => $q->select(['users.id', 'users.name', 'users.email', 'users.active'])->orderBy('users.name'),
                    'eventos'  => fn ($q) => $q->with('user:id,name')->latest('created_at')->limit(20),
                ])
                ->find($this->clinicaAbertaId)
            : null;

        return view('livewire.plataforma-index', [
            'clinicas'    => $clinicas,
            'indicadores' => $indicadores->calcular(),
            'aberta'      => $aberta,
            'uso'         => $aberta ? $indicadores->uso($aberta) : null,
            'situacoes'   => StatusClinica::cases(),
        ])->layout('layouts.app', ['title' => 'Clínicas']);
    }
}
