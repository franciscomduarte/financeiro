<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\GerenciarRecorrenciaAction;
use App\Enums\FormaPagamento;
use App\Models\Recorrencia;
use App\Models\Transacao;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/** Aba "Recorrências" de Lançamentos: modelos que geram os lançamentos de cada mês. */
class RecorrenciaIndex extends Component
{
    use Concerns\MensagemDeErro, WithPagination;

    #[Url(history: true)]
    public string $filtro = 'ativas';

    // ─── Edição ─────────────────────────────────────────────────
    public ?string $editandoId     = null;
    public string $descricao       = '';
    public string $categoria       = '';
    public string $valorBruto      = '';
    public string $formaPagamento  = 'pix';
    public string $dataFim         = '';
    public bool   $lancarComoPago  = false;
    public string $observacoes     = '';
    public string $tipoEditando    = 'saida';

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function updatedFiltro(): void
    {
        $this->resetPage();
    }

    public function editar(string $id): void
    {
        $r = Recorrencia::findOrFail($id);

        $this->editandoId     = $r->id;
        $this->descricao      = $r->descricao;
        $this->categoria      = $r->categoria;
        $this->valorBruto     = number_format((float) $r->valor_bruto, 2, '.', '');
        $this->formaPagamento = $r->forma_pagamento->value;
        $this->dataFim        = $r->data_fim?->toDateString() ?? '';
        $this->lancarComoPago = $r->lancar_como_pago;
        $this->observacoes    = (string) $r->observacoes;
        $this->tipoEditando   = $r->tipo->value;
        $this->resetErrorBag();
    }

    public function fecharEdicao(): void
    {
        $this->editandoId = null;
    }

    public function salvar(GerenciarRecorrenciaAction $action): void
    {
        // Aceita o formato brasileiro (3.200,50) e o simples (3200.50)
        if (str_contains($this->valorBruto, ',')) {
            $this->valorBruto = str_replace(['.', ','], ['', '.'], $this->valorBruto);
        }
        $this->validate([
            'descricao'      => 'required|string|max:255',
            'categoria'      => 'required|string|max:100',
            'valorBruto'     => 'required|numeric|min:0.01|max:99999999.99',
            'formaPagamento' => ['required', Rule::enum(FormaPagamento::class)],
            'dataFim'        => 'nullable|date',
            'lancarComoPago' => 'boolean',
            'observacoes'    => 'nullable|string|max:1000',
        ]);

        try {
            $action->atualizar(Recorrencia::findOrFail($this->editandoId), [
                'descricao'        => $this->descricao,
                'categoria'        => $this->categoria,
                'valor_bruto'      => (float) $this->valorBruto,
                'forma_pagamento'  => $this->formaPagamento,
                'data_fim'         => $this->dataFim ?: null,
                'lancar_como_pago' => $this->lancarComoPago,
                'observacoes'      => $this->observacoes ?: null,
            ]);
            $this->editandoId   = null;
            $this->flashSucesso = 'Recorrência salva. As mudanças valem dos próximos lançamentos em diante.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar a recorrência');
        }
    }

    public function pausar(string $id, GerenciarRecorrenciaAction $action): void
    {
        $this->executar(fn () => $action->pausar(Recorrencia::findOrFail($id)), 'Recorrência pausada. Nenhum lançamento novo até você retomar.');
    }

    public function retomar(string $id, GerenciarRecorrenciaAction $action): void
    {
        $this->executar(fn () => $action->retomar(Recorrencia::findOrFail($id)), 'Recorrência retomada a partir deste mês.');
    }

    public function encerrar(string $id, GerenciarRecorrenciaAction $action): void
    {
        $this->executar(fn () => $action->encerrar(Recorrencia::findOrFail($id)), 'Recorrência encerrada. Os lançamentos já criados continuam na lista.');
    }

    private function executar(callable $acao, string $sucesso): void
    {
        $this->flashSucesso = $this->flashErro = null;

        try {
            $acao();
            $this->flashSucesso = $sucesso;
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível alterar a recorrência');
        }
    }

    public function render(): View
    {
        $recorrencias = Recorrencia::query()
            ->select(['id', 'tipo', 'categoria', 'descricao', 'valor_bruto', 'forma_pagamento', 'frequencia',
                'dia_vencimento', 'proxima_data', 'data_fim', 'lancar_como_pago', 'ativa', 'encerrada_em'])
            ->when($this->filtro === 'ativas', fn ($q) => $q->where('ativa', true))
            ->when($this->filtro === 'pausadas', fn ($q) => $q->where('ativa', false)->whereNull('encerrada_em'))
            ->when($this->filtro === 'encerradas', fn ($q) => $q->whereNotNull('encerrada_em'))
            ->orderByDesc('ativa')
            ->orderBy('proxima_data')
            ->paginate(20);

        // Compromisso mensal das recorrências ativas (anual/semestral/trimestral divididos por mês)
        $ativas = Recorrencia::query()->where('ativa', true)->select(['id', 'tipo', 'valor_bruto', 'frequencia'])->limit(500)->get();
        $mensal = fn (string $tipo) => $ativas->where('tipo.value', $tipo)->sum(fn (Recorrencia $r) => $r->valorMensal());

        return view('livewire.recorrencia-index', [
            'recorrencias'     => $recorrencias,
            'totalAtivas'      => $ativas->count(),
            'saidasMensais'    => $mensal('saida'),
            'entradasMensais'  => $mensal('entrada'),
            'formasPagamento'  => array_filter(FormaPagamento::cases(), fn (FormaPagamento $f) => $f->parcelas() === 1),
            'categorias'       => $this->tipoEditando === 'entrada' ? Transacao::CATEGORIAS_ENTRADA : Transacao::CATEGORIAS_SAIDA,
        ])->layout('layouts.app', ['title' => 'Recorrências']);
    }
}
