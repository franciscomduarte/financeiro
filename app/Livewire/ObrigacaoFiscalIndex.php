<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateObrigacaoFiscalAction;
use App\Actions\LancarGuiaFiscalAction;
use App\Actions\PagarGuiaFiscalAction;
use App\Actions\UpdateObrigacaoFiscalAction;
use App\Actions\UploadGuiaFiscalAction;
use App\Enums\PeriodicidadeFiscal;
use App\Enums\TipoTributo;
use App\Models\ObrigacaoFiscal;
use App\Models\ObrigacaoFiscalLancamento;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class ObrigacaoFiscalIndex extends Component
{
    use WithFileUploads, WithPagination;

    // ─── Filtros ────────────────────────────────────────────────
    public string $filtroStatus      = '';
    public string $filtroObrigacaoId = '';

    // ─── Estado dos modais ──────────────────────────────────────
    public bool $modalCriar       = false;
    public bool $modalEditar      = false;
    public bool $modalLancar      = false;
    public bool $modalPagar       = false;
    public bool $modalUploadGuia  = false;

    public ?string $obrigacaoEditandoId = null;
    public ?string $obrigacaoLancarId   = null;
    public ?string $lancamentoId        = null;
    public ?string $lancamentoUploadId  = null;

    // ─── Upload guia ────────────────────────────────────────────
    /** @var mixed */
    public $arquivoGuia = null;

    // ─── Formulário Obrigação ───────────────────────────────────
    public string $tipoTributo     = 'das_simples';
    public string $descricao       = '';
    public string $codigoReceita   = '';
    public string $periodicidade   = 'mensal';
    public string $diaVencimento   = '';
    public string $statusOb        = 'ativo';
    public string $observacoes     = '';

    // ─── Formulário Lançar Guia ─────────────────────────────────
    public string $competencia     = '';
    public string $dataVencimento  = '';
    public string $valorPrincipal  = '';
    public string $valorMulta      = '';
    public string $valorJuros      = '';
    public string $codigoBarras    = '';
    public string $lancarObs       = '';

    // ─── Formulário Pagar ───────────────────────────────────────
    public string $formaPagamento    = 'pix';
    public string $dataPagamento     = '';
    public string $pagarValorMulta   = '';
    public string $pagarValorJuros   = '';
    public string $numeroAutenticacao = '';

    // ─── Flash ──────────────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    protected function queryString(): array
    {
        return [
            'filtroStatus'      => ['except' => '', 'as' => 'status'],
            'filtroObrigacaoId' => ['except' => '', 'as' => 'ob'],
        ];
    }

    public function updatingFiltroStatus(): void      { $this->resetPage(); }
    public function updatingFiltroObrigacaoId(): void { $this->resetPage(); }

    // ─── Modal Criar ────────────────────────────────────────────
    public function abrirModalCriar(): void
    {
        $this->resetFormularioOb();
        $this->modalCriar = true;
    }

    public function salvar(CreateObrigacaoFiscalAction $action): void
    {
        $this->validate($this->rulesOb());
        try {
            $action->execute($this->dadosOb());
            $this->modalCriar  = false;
            $this->resetFormularioOb();
            $this->flashSucesso = 'Obrigação cadastrada com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao cadastrar: ' . $e->getMessage();
        }
    }

    // ─── Modal Editar ───────────────────────────────────────────
    public function abrirModalEditar(string $id): void
    {
        $ob = ObrigacaoFiscal::findOrFail($id);
        $this->obrigacaoEditandoId = $id;
        $this->tipoTributo   = $ob->tipo_tributo->value;
        $this->descricao     = $ob->descricao;
        $this->codigoReceita = $ob->codigo_receita ?? '';
        $this->periodicidade = $ob->periodicidade->value;
        $this->diaVencimento = $ob->dia_vencimento ? (string) $ob->dia_vencimento : '';
        $this->statusOb      = $ob->status;
        $this->observacoes   = $ob->observacoes ?? '';
        $this->modalEditar   = true;
    }

    public function atualizar(UpdateObrigacaoFiscalAction $action): void
    {
        $this->validate($this->rulesOb());
        try {
            $ob = ObrigacaoFiscal::findOrFail($this->obrigacaoEditandoId);
            $action->execute($ob, $this->dadosOb());
            $this->modalEditar  = false;
            $this->resetFormularioOb();
            $this->flashSucesso = 'Obrigação atualizada com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao atualizar: ' . $e->getMessage();
        }
    }

    // ─── Modal Lançar Guia ──────────────────────────────────────
    public function abrirModalLancar(string $obId): void
    {
        $ob = ObrigacaoFiscal::findOrFail($obId);
        $this->obrigacaoLancarId = $obId;
        $this->competencia    = now()->format('Y-m');
        $this->dataVencimento = $ob->dia_vencimento
            ? now()->day($ob->dia_vencimento)->format('Y-m-d')
            : now()->endOfMonth()->format('Y-m-d');
        $this->valorPrincipal = '';
        $this->valorMulta     = '';
        $this->valorJuros     = '';
        $this->codigoBarras   = '';
        $this->lancarObs      = '';
        $this->modalLancar    = true;
    }

    public function lancarGuia(LancarGuiaFiscalAction $action): void
    {
        $this->validate([
            'competencia'    => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'dataVencimento' => ['required', 'date'],
            'valorPrincipal' => ['required', 'numeric', 'min:0.01'],
            'valorMulta'     => ['nullable', 'numeric', 'min:0'],
            'valorJuros'     => ['nullable', 'numeric', 'min:0'],
        ]);
        try {
            $ob = ObrigacaoFiscal::findOrFail($this->obrigacaoLancarId);
            $action->execute($ob, [
                'competencia'     => $this->competencia,
                'data_vencimento' => $this->dataVencimento,
                'valor_principal' => $this->valorPrincipal,
                'valor_multa'     => $this->valorMulta ?: 0,
                'valor_juros'     => $this->valorJuros ?: 0,
                'codigo_barras'   => $this->codigoBarras ?: null,
                'observacoes'     => $this->lancarObs ?: null,
            ]);
            $this->modalLancar  = false;
            $this->flashSucesso = 'Guia lançada com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao lançar guia: ' . $e->getMessage();
        }
    }

    // ─── Modal Pagar ────────────────────────────────────────────
    public function abrirModalPagar(string $id): void
    {
        $lancamento = ObrigacaoFiscalLancamento::findOrFail($id);
        $this->lancamentoId        = $id;
        $this->formaPagamento      = 'pix';
        $this->dataPagamento       = now()->toDateString();
        $this->pagarValorMulta     = (float) $lancamento->valor_multa > 0
            ? (string) $lancamento->getRawOriginal('valor_multa') : '';
        $this->pagarValorJuros     = (float) $lancamento->valor_juros > 0
            ? (string) $lancamento->getRawOriginal('valor_juros') : '';
        $this->numeroAutenticacao  = '';
        $this->modalPagar          = true;
    }

    public function pagar(PagarGuiaFiscalAction $action): void
    {
        $this->validate([
            'formaPagamento'     => ['required', 'string'],
            'dataPagamento'      => ['required', 'date'],
            'pagarValorMulta'    => ['nullable', 'numeric', 'min:0'],
            'pagarValorJuros'    => ['nullable', 'numeric', 'min:0'],
            'numeroAutenticacao' => ['nullable', 'string', 'max:50'],
        ]);
        try {
            $lancamento = ObrigacaoFiscalLancamento::findOrFail($this->lancamentoId);
            $action->execute($lancamento, [
                'forma_pagamento'     => $this->formaPagamento,
                'data_pagamento'      => $this->dataPagamento,
                'valor_multa'         => $this->pagarValorMulta !== '' ? (float) $this->pagarValorMulta : null,
                'valor_juros'         => $this->pagarValorJuros !== '' ? (float) $this->pagarValorJuros : null,
                'numero_autenticacao' => $this->numeroAutenticacao ?: null,
            ]);
            $this->modalPagar   = false;
            $this->flashSucesso = 'Guia paga e lançada no financeiro!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao registrar pagamento: ' . $e->getMessage();
        }
    }

    // ─── Modal Upload Arquivo de Guia ───────────────────────────
    public function abrirModalUploadGuia(string $id): void
    {
        $this->lancamentoUploadId = $id;
        $this->arquivoGuia        = null;
        $this->modalUploadGuia    = true;
    }

    public function uploadArquivoGuia(UploadGuiaFiscalAction $action): void
    {
        $this->validate([
            'arquivoGuia' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,docx'],
        ]);

        try {
            $lancamento = ObrigacaoFiscalLancamento::findOrFail($this->lancamentoUploadId);
            $action->execute($lancamento, $this->arquivoGuia);
            $this->modalUploadGuia = false;
            $this->arquivoGuia     = null;
            $this->flashSucesso    = 'Arquivo enviado com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao enviar arquivo: ' . $e->getMessage();
        }
    }

    // ─── Fechar modais ──────────────────────────────────────────
    public function fecharModais(): void
    {
        $this->modalCriar      = false;
        $this->modalEditar     = false;
        $this->modalLancar     = false;
        $this->modalPagar      = false;
        $this->modalUploadGuia = false;
        $this->arquivoGuia     = null;
        $this->flashErro       = null;
        $this->resetFormularioOb();
    }

    // ─── Helpers privados ───────────────────────────────────────
    private function resetFormularioOb(): void
    {
        $this->tipoTributo         = 'das_simples';
        $this->descricao           = '';
        $this->codigoReceita       = '';
        $this->periodicidade       = 'mensal';
        $this->diaVencimento       = '';
        $this->statusOb            = 'ativo';
        $this->observacoes         = '';
        $this->obrigacaoEditandoId = null;
        $this->obrigacaoLancarId   = null;
        $this->lancamentoId        = null;
    }

    private function dadosOb(): array
    {
        return [
            'tipo_tributo'   => $this->tipoTributo,
            'descricao'      => $this->descricao,
            'codigo_receita' => $this->codigoReceita ?: null,
            'periodicidade'  => $this->periodicidade,
            'dia_vencimento' => $this->diaVencimento ? (int) $this->diaVencimento : null,
            'status'         => $this->statusOb,
            'observacoes'    => $this->observacoes ?: null,
        ];
    }

    private function rulesOb(): array
    {
        return [
            'tipoTributo'   => ['required', 'in:das_simples,darf,gps_inss,fgts,iss,irrf,outro'],
            'descricao'     => ['required', 'string', 'max:255'],
            'codigoReceita' => ['nullable', 'string', 'max:10'],
            'periodicidade' => ['required', 'in:mensal,trimestral,semestral,anual,eventual'],
            'diaVencimento' => ['nullable', 'integer', 'min:1', 'max:31'],
            'statusOb'      => ['required', 'in:ativo,inativo'],
        ];
    }

    public function render(): View
    {
        $obrigacoes = ObrigacaoFiscal::query()
            ->with('ultimoLancamento')
            ->orderBy('tipo_tributo')
            ->orderBy('descricao')
            ->get();

        $lancamentos = ObrigacaoFiscalLancamento::query()
            ->with('obrigacaoFiscal')
            ->when($this->filtroStatus !== '', fn ($q) => $q->where('status', $this->filtroStatus))
            ->when($this->filtroObrigacaoId !== '', fn ($q) => $q->where('obrigacao_fiscal_id', $this->filtroObrigacaoId))
            ->orderByDesc('competencia')
            ->orderBy('data_vencimento')
            ->paginate(20);

        $mesAtual        = now()->format('Y-m');
        $totalPendente   = (float) ObrigacaoFiscalLancamento::whereIn('status', ['pendente', 'vencido'])->sum(\DB::raw('valor_principal + valor_multa + valor_juros'));
        $totalVencidas   = ObrigacaoFiscalLancamento::where('status', 'vencido')->count();
        $totalPagoMes    = (float) ObrigacaoFiscalLancamento::where('status', 'pago')
            ->where('competencia', $mesAtual)
            ->sum(\DB::raw('valor_principal + valor_multa + valor_juros'));

        $lancamentoParaPagar = $this->lancamentoId
            ? ObrigacaoFiscalLancamento::with('obrigacaoFiscal')->find($this->lancamentoId)
            : null;

        return view('livewire.obrigacao-fiscal-index', [
            'obrigacoes'          => $obrigacoes,
            'lancamentos'         => $lancamentos,
            'totalPendente'       => $totalPendente,
            'totalVencidas'       => $totalVencidas,
            'totalPagoMes'        => $totalPagoMes,
            'lancamentoParaPagar' => $lancamentoParaPagar,
            'tipos'               => TipoTributo::cases(),
            'periodicidades'      => PeriodicidadeFiscal::cases(),
        ])->layout('layouts.app', ['title' => 'Obrigações Fiscais']);
    }
}
