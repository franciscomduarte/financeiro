<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Orcamentos\AprovarOrcamentoAction;
use App\Actions\Orcamentos\EnviarOrcamentoWhatsAppAction;
use App\Actions\Orcamentos\RecusarOrcamentoAction;
use App\Actions\Orcamentos\SalvarOrcamentoAction;
use App\Enums\FormaPagamento;
use App\Enums\StatusOrcamento;
use App\Models\Orcamento;
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

/** Orçamentos: monta, envia ao paciente e, aprovado, vira receita e pacotes de sessões. */
class OrcamentoIndex extends Component
{
    use Concerns\EscolhePaciente;
    use Concerns\MensagemDeErro;
    use WithPagination;

    private const MAX_ITENS = 30;

    #[Url(as: 'q', except: '')]
    public string $busca = '';

    #[Url(as: 'situacao', except: '')]
    public string $filtroStatus = '';

    // ─── Formulário ─────────────────────────────────────────────
    public bool $modal = false;
    public ?string $editandoId = null;
    /** @var array<int, array{procedimento_id: string, descricao: string, quantidade: string, valor_unitario: string}> */
    public array $itens = [];
    public string $desconto = '0';
    public string $validade = '';
    public string $formaPagamento = '';
    public string $observacoes = '';

    // ─── Aprovação ──────────────────────────────────────────────
    public ?string $aprovandoId = null;
    public string $aprovarForma = 'pix';
    public string $aprovarCategoria = '';
    public bool $aprovarPago = true;
    public string $aprovarValidade = '';

    public ?string $detalheId = null;

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
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
    public function detalhe(): ?Orcamento
    {
        return $this->detalheId
            ? Orcamento::query()->with(['paciente:id,nome,telefone', 'itens', 'autor:id,name', 'pacotes:id,orcamento_id,nome,sessoes_total,sessoes_usadas,status'])
                ->find($this->detalheId)
            : null;
    }

    /** Subtotal e total calculados na tela (o valor que vale é recalculado ao salvar). */
    #[Computed]
    public function totais(): array
    {
        $subtotal = array_sum(array_map(fn ($i) => max(0, (int) $i['quantidade']) * (float) str_replace(',', '.', (string) $i['valor_unitario']), $this->itens));
        $desconto = (float) str_replace(',', '.', $this->desconto);

        return ['subtotal' => $subtotal, 'total' => max(0, $subtotal - $desconto)];
    }

    // ─── Formulário ─────────────────────────────────────────────

    public function novo(): void
    {
        $this->limparFlash();
        $this->resetValidation();
        $this->reset('editandoId', 'observacoes', 'formaPagamento', 'buscaPaciente', 'pacienteId', 'pacienteNome');
        $this->itens    = [$this->itemVazio()];
        $this->desconto = '0';
        $this->validade = now()->addDays(15)->toDateString();
        $this->modal    = true;
    }

    public function editar(string $id): void
    {
        $o = Orcamento::query()->with(['paciente:id,nome', 'itens'])->findOrFail($id);
        if ($o->status !== StatusOrcamento::Aberto) {
            $this->flashErro = 'Só orçamentos em aberto podem ser alterados.';

            return;
        }

        $this->limparFlash();
        $this->resetValidation();
        $this->detalheId      = null;
        $this->editandoId     = $o->id;
        $this->pacienteId     = $o->paciente_id;
        $this->pacienteNome   = $this->buscaPaciente = (string) $o->paciente?->nome;
        $this->itens          = $o->itens->map(fn ($i) => [
            'procedimento_id' => (string) $i->procedimento_id,
            'descricao'       => $i->descricao,
            'quantidade'      => (string) $i->quantidade,
            'valor_unitario'  => number_format((float) $i->valor_unitario, 2, '.', ''),
        ])->all();
        $this->desconto       = number_format((float) $o->desconto, 2, '.', '');
        $this->validade       = $o->validade->toDateString();
        $this->formaPagamento = $o->forma_pagamento?->value ?? '';
        $this->observacoes    = (string) $o->observacoes;
        $this->modal          = true;
    }

    public function adicionarItem(): void
    {
        if (count($this->itens) < self::MAX_ITENS) {
            $this->itens[] = $this->itemVazio();
        }
    }

    public function removerItem(int $indice): void
    {
        unset($this->itens[$indice]);
        $this->itens = array_values($this->itens) ?: [$this->itemVazio()];
    }

    /** Escolher o procedimento preenche descrição e valor do item. */
    public function updatedItens(mixed $valor, string $chave): void
    {
        [$indice, $campo] = array_pad(explode('.', $chave), 2, null);
        if ($campo !== 'procedimento_id' || ! isset($this->itens[(int) $indice])) {
            return;
        }

        $proc = $this->procedimentos->firstWhere('id', (int) $valor);
        if ($proc) {
            $this->itens[(int) $indice]['descricao']      = $proc->nome;
            $this->itens[(int) $indice]['valor_unitario'] = number_format((float) $proc->valor, 2, '.', '');
        }
    }

    public function salvar(SalvarOrcamentoAction $salvar): void
    {
        $this->limparFlash();
        $this->validate([
            'pacienteId'             => ['required', 'uuid'],
            'itens'                  => ['required', 'array', 'min:1', 'max:' . self::MAX_ITENS],
            'itens.*.procedimento_id' => ['nullable', 'integer'],
            'itens.*.descricao'      => ['required', 'string', 'max:150'],
            'itens.*.quantidade'     => ['required', 'integer', 'min:1', 'max:200'],
            'itens.*.valor_unitario' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'desconto'               => ['required', 'numeric', 'min:0'],
            'validade'               => ['required', 'date', 'after_or_equal:today'],
            'formaPagamento'         => ['nullable', Rule::in(PacoteIndex::formasDePagamento())],
            'observacoes'            => ['nullable', 'string', 'max:2000'],
        ], [
            'pacienteId.required'           => 'Escolha o paciente na lista.',
            'itens.*.descricao.required'    => 'Escolha o procedimento ou descreva o item.',
            'itens.*.quantidade.min'        => 'Pelo menos 1 sessão.',
            'itens.*.valor_unitario.required' => 'Informe o valor.',
            'validade.after_or_equal'       => 'A validade não pode ser no passado.',
        ]);

        try {
            $orcamento = $salvar->execute($this->editandoId, [
                'paciente_id'     => $this->pacienteId,
                'validade'        => $this->validade,
                'desconto'        => (float) $this->desconto,
                'observacoes'     => trim($this->observacoes) ?: null,
                'forma_pagamento' => $this->formaPagamento ?: null,
                'itens'           => array_map(fn ($i) => [
                    'procedimento_id' => $i['procedimento_id'] ?: null,
                    'descricao'       => $i['descricao'],
                    'quantidade'      => (int) $i['quantidade'],
                    'valor_unitario'  => (float) $i['valor_unitario'],
                ], $this->itens),
            ]);
            $this->modal        = false;
            $this->detalheId    = $orcamento->id;
            $this->flashSucesso = "Orçamento {$orcamento->codigo()} salvo.";
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar o orçamento');
        }
    }

    // ─── Ações ──────────────────────────────────────────────────

    public function enviarWhatsapp(string $id, EnviarOrcamentoWhatsAppAction $enviar): void
    {
        $this->limparFlash();

        try {
            $enviar->execute($id);
            $this->flashSucesso = 'Orçamento enviado por WhatsApp.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível enviar por WhatsApp');
        }
    }

    public function abrirAprovacao(string $id): void
    {
        $o = Orcamento::query()->select(['id', 'forma_pagamento'])->findOrFail($id);

        $this->limparFlash();
        $this->resetValidation();
        $this->aprovandoId      = $o->id;
        $this->aprovarForma     = $o->forma_pagamento?->value ?? 'pix';
        $this->aprovarCategoria = '';
        $this->aprovarPago      = true;
        $this->aprovarValidade  = '';
    }

    public function aprovar(AprovarOrcamentoAction $aprovar): void
    {
        $this->validate([
            'aprovarForma'     => ['required', Rule::in(PacoteIndex::formasDePagamento())],
            'aprovarCategoria' => ['required', Rule::in(\App\Models\PlanoConta::nomes('entrada'))],
            'aprovarValidade'  => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'aprovarCategoria.required' => 'Escolha a categoria da receita.',
        ]);

        try {
            $o = $aprovar->execute($this->aprovandoId, FormaPagamento::from($this->aprovarForma), $this->aprovarCategoria,
                $this->aprovarPago, $this->aprovarValidade ?: null);
            $this->aprovandoId  = null;
            $this->detalheId    = $o->id;
            $this->flashSucesso = "Orçamento {$o->codigo()} aprovado. A receita foi lançada e as sessões viraram pacotes do paciente.";
        } catch (Throwable $e) {
            $this->aprovandoId = null;
            $this->flashErro   = $this->mensagemDeErro($e, 'Não foi possível aprovar o orçamento');
        }
    }

    public function recusar(string $id, RecusarOrcamentoAction $recusar): void
    {
        $this->limparFlash();

        try {
            $recusar->execute($id);
            $this->flashSucesso = 'Orçamento marcado como recusado.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível recusar o orçamento');
        }
    }

    /** @return array{procedimento_id: string, descricao: string, quantidade: string, valor_unitario: string} */
    private function itemVazio(): array
    {
        return ['procedimento_id' => '', 'descricao' => '', 'quantidade' => '1', 'valor_unitario' => ''];
    }

    private function limparFlash(): void
    {
        $this->flashSucesso = $this->flashErro = null;
    }

    public function render(): View
    {
        $orcamentos = Orcamento::query()
            ->select(['id', 'numero', 'paciente_id', 'status', 'validade', 'total', 'enviado_em', 'created_at'])
            ->with('paciente:id,nome')
            ->when(StatusOrcamento::tryFrom($this->filtroStatus), fn ($q, $s) => $q->where('status', $s))
            ->when(trim($this->busca) !== '', fn ($q) => $q->whereHas('paciente', fn ($p) => $p->where('nome', 'ilike', '%' . trim($this->busca) . '%')))
            ->latest()
            ->paginate(20);

        $inicioMes = now()->startOfMonth();
        $resumo    = [
            'abertos'        => Orcamento::query()->where('status', StatusOrcamento::Aberto)->whereDate('validade', '>=', today())->count(),
            'valor_aberto'   => (float) Orcamento::query()->where('status', StatusOrcamento::Aberto)->whereDate('validade', '>=', today())->sum('total'),
            'aprovados_mes'  => (float) Orcamento::query()->where('status', StatusOrcamento::Aprovado)->where('decidido_em', '>=', $inicioMes)->sum('total'),
            'decididos_mes'  => Orcamento::query()->whereIn('status', [StatusOrcamento::Aprovado, StatusOrcamento::Recusado])->where('decidido_em', '>=', $inicioMes)->count(),
            'aprovados_qtd'  => Orcamento::query()->where('status', StatusOrcamento::Aprovado)->where('decidido_em', '>=', $inicioMes)->count(),
        ];

        return view('livewire.orcamento-index', [
            'orcamentos' => $orcamentos,
            'resumo'     => $resumo,
            'status'     => StatusOrcamento::cases(),
            'formas'     => array_map(fn ($v) => FormaPagamento::from($v), PacoteIndex::formasDePagamento()),
            'categorias' => \App\Models\PlanoConta::nomes('entrada'),
        ])->layout('layouts.app', ['title' => 'Orçamentos']);
    }
}
