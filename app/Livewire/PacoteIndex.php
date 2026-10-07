<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Pacotes\CancelarPacoteAction;
use App\Actions\Pacotes\VenderPacoteAction;
use App\Enums\FormaPagamento;
use App\Enums\StatusPacote;
use App\Models\Pacote;
use App\Models\PacoteSessao;
use App\Models\Procedimento;
use App\Models\Transacao;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/** Pacotes de sessões: venda, saldo de cada paciente e cancelamento. */
class PacoteIndex extends Component
{
    use Concerns\EscolhePaciente;
    use Concerns\MensagemDeErro;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $busca = '';

    #[Url(as: 'situacao', except: 'ativo')]
    public string $filtroStatus = 'ativo';

    public bool $modal = false;
    public string $procedimentoId = '';
    public string $nome = '';
    public string $sessoes = '10';
    public string $valorTotal = '';
    public string $validade = '';
    public string $formaPagamento = 'pix';
    public string $categoria = '';
    public bool $pago = true;

    public ?string $detalheId = null;

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        // Vindo da ficha do paciente: já abre a venda para ele
        if ($id = request()->query('paciente')) {
            $this->novo();
            $this->escolherPaciente((string) $id);
        }
    }

    public function updatingBusca(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function procedimentos(): Collection
    {
        return Procedimento::query()->select(['id', 'nome', 'valor'])->where('ativo', true)->orderBy('nome')->limit(200)->get();
    }

    #[Computed]
    public function detalhe(): ?Pacote
    {
        return $this->detalheId
            ? Pacote::query()->with([
                'paciente:id,nome',
                'transacao:id,status,valor_bruto,forma_pagamento',
                'sessoes' => fn ($q) => $q->select(['id', 'pacote_id', 'agendamento_id', 'profissional_id', 'usada_em'])
                    ->with('profissional:id,nome')->orderByDesc('usada_em')->limit(100),
            ])->find($this->detalheId)
            : null;
    }

    public function novo(): void
    {
        $this->limparFlash();
        $this->resetValidation();
        $this->reset('procedimentoId', 'nome', 'valorTotal', 'validade', 'categoria', 'buscaPaciente', 'pacienteId', 'pacienteNome');
        $this->sessoes        = '10';
        $this->formaPagamento = 'pix';
        $this->pago           = true;
        $this->modal          = true;
    }

    /** Ao escolher o procedimento, sugere nome e valor (valor do procedimento × sessões). */
    public function updatedProcedimentoId(): void
    {
        $this->sugerir();
    }

    public function updatedSessoes(): void
    {
        $this->sugerir();
    }

    private function sugerir(): void
    {
        $proc = $this->procedimentos->firstWhere('id', (int) $this->procedimentoId);
        if ($proc === null) {
            return;
        }

        $sessoes          = max(1, (int) $this->sessoes);
        $this->nome       = "{$sessoes} sessões de {$proc->nome}";
        $this->valorTotal = number_format((float) $proc->valor * $sessoes, 2, '.', '');
    }

    public function vender(VenderPacoteAction $vender): void
    {
        $this->limparFlash();
        $this->validate([
            'pacienteId'     => ['required', 'uuid'],
            'procedimentoId' => ['nullable', 'integer'],
            'nome'           => ['required', 'string', 'max:150'],
            'sessoes'        => ['required', 'integer', 'min:1', 'max:200'],
            'valorTotal'     => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'validade'       => ['nullable', 'date', 'after_or_equal:today'],
            'formaPagamento' => ['required', Rule::in(self::formasDePagamento())],
            'categoria'      => ['required', Rule::in(\App\Models\PlanoConta::nomes('entrada'))],
        ], [
            'pacienteId.required'      => 'Escolha o paciente na lista.',
            'nome.required'            => 'Dê um nome ao pacote. Ex.: 10 sessões de laser.',
            'sessoes.min'              => 'O pacote precisa de pelo menos 1 sessão.',
            'valorTotal.required'      => 'Informe o valor do pacote.',
            'valorTotal.min'           => 'O valor deve ser maior que zero.',
            'validade.after_or_equal'  => 'A validade não pode ser no passado.',
            'categoria.required'       => 'Escolha a categoria da receita.',
        ]);

        try {
            $vender->execute([
                'paciente_id'     => $this->pacienteId,
                'procedimento_id' => $this->procedimentoId !== '' ? (int) $this->procedimentoId : null,
                'nome'            => $this->nome,
                'sessoes'         => (int) $this->sessoes,
                'valor_total'     => (float) $this->valorTotal,
                'validade'        => $this->validade ?: null,
                'forma_pagamento' => $this->formaPagamento,
                'categoria'       => $this->categoria,
                'pago'            => $this->pago,
            ]);
            $this->modal        = false;
            $this->flashSucesso = 'Pacote vendido. A receita já está em Lançamentos.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível vender o pacote');
        }
    }

    public function cancelar(string $id, CancelarPacoteAction $cancelar): void
    {
        $this->limparFlash();

        try {
            $cancelar->execute($id);
            $this->detalheId    = null;
            $this->flashSucesso = 'Pacote cancelado. Se houver devolução, registre o estorno em Lançamentos.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível cancelar o pacote');
        }
    }

    /** @return array<int, string> */
    public static function formasDePagamento(): array
    {
        return array_map(fn (FormaPagamento $f) => $f->value,
            array_values(array_filter(FormaPagamento::cases(), fn (FormaPagamento $f) => $f !== FormaPagamento::AportePessoal)));
    }

    private function limparFlash(): void
    {
        $this->flashSucesso = $this->flashErro = null;
    }

    public function render(): View
    {
        $pacotes = Pacote::query()
            ->select(['id', 'paciente_id', 'nome', 'sessoes_total', 'sessoes_usadas', 'valor_total', 'validade', 'status', 'created_at'])
            ->with('paciente:id,nome')
            ->when(StatusPacote::tryFrom($this->filtroStatus), fn ($q, $s) => $q->where('status', $s))
            ->when(trim($this->busca) !== '', fn ($q) => $q->whereHas('paciente', fn ($p) => $p->where('nome', 'ilike', '%' . trim($this->busca) . '%')))
            ->latest()
            ->paginate(20);

        $resumo = [
            'ativos'          => Pacote::query()->where('status', StatusPacote::Ativo)->count(),
            'sessoes_a_usar'  => (int) Pacote::query()->where('status', StatusPacote::Ativo)->selectRaw('coalesce(sum(sessoes_total - sessoes_usadas), 0) as s')->value('s'),
            'vendido_no_mes'  => (float) Pacote::query()->where('status', '!=', StatusPacote::Cancelado)
                ->where('created_at', '>=', now()->startOfMonth())->sum('valor_total'),
            'sessoes_no_mes'  => PacoteSessao::query()->where('usada_em', '>=', now()->startOfMonth()->toDateString())->count(),
        ];

        return view('livewire.pacote-index', [
            'pacotes'    => $pacotes,
            'resumo'     => $resumo,
            'status'     => StatusPacote::cases(),
            'formas'     => array_map(fn ($v) => FormaPagamento::from($v), self::formasDePagamento()),
            'categorias' => \App\Models\PlanoConta::nomes('entrada'),
        ])->layout('layouts.app', ['title' => 'Pacotes']);
    }
}
