<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Financeiro\BaixarTransacaoAction;
use App\Actions\Financeiro\EstornarBaixaAction;
use App\Enums\FormaPagamento;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\ContaFinanceira;
use App\Models\Transacao;
use App\Models\TransacaoBaixa;
use App\Support\Dinheiro;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * Contas a pagar (saídas) e a receber (entradas): o que está em aberto por vencimento, com baixa
 * rápida (total ou parcial, juros, multa, desconto e conta) e o que foi pago/recebido no mês.
 */
class ContasPagarReceber extends Component
{
    use Concerns\MensagemDeErro;
    use WithPagination;

    public const FILTROS = [
        'vencidos' => 'Vencidos',
        'hoje'     => 'Vencem hoje',
        'semana'   => 'Próximos 7 dias',
        'mes'      => 'Do mês',
        'abertos'  => 'Todos em aberto',
        'quitados' => 'Pagos no mês',
    ];

    #[Locked]
    public string $tipo = 'saida';

    #[Url(except: 'abertos')]
    public string $filtro = 'abertos';

    #[Url]
    public string $mes = '';

    #[Url(except: '')]
    public string $busca = '';

    // ─── Baixa ──────────────────────────────────────────────────
    public ?string $baixaId     = null;
    public string $baixaValor    = '';
    public string $baixaData     = '';
    public string $baixaConta    = '';
    public string $baixaForma    = '';
    public string $baixaJuros    = '';
    public string $baixaMulta    = '';
    public string $baixaDesconto = '';
    public string $baixaObs      = '';
    public bool $baixaEncargos   = false;
    public string $baixaCartao   = 'padrao'; // padrao | parcelado | antecipado

    // ─── Detalhe ────────────────────────────────────────────────
    public ?string $detalheId = null;

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(string $tipo = 'saida'): void
    {
        $this->tipo = TipoTransacao::from($tipo)->value;
        if (! array_key_exists($this->filtro, self::FILTROS)) {
            $this->filtro = 'abertos';
        }
        if (! preg_match('/^\d{4}-\d{2}$/', $this->mes)) {
            $this->mes = today()->format('Y-m');
        }
    }

    public function updated(string $prop): void
    {
        if (in_array($prop, ['filtro', 'mes', 'busca'], true)) {
            $this->resetPage();
        }
    }

    private function aPagar(): bool
    {
        return $this->tipo === TipoTransacao::Saida->value;
    }

    private function mesRef(): CarbonImmutable
    {
        return $this->mes !== '' ? CarbonImmutable::createFromFormat('!Y-m', $this->mes) : CarbonImmutable::today()->startOfMonth();
    }

    // ─── Resumo ─────────────────────────────────────────────────
    /** @return array{vencidos: array{0: int, 1: float}, hoje: array{0: int, 1: float}, semana: array{0: int, 1: float}, abertos: array{0: int, 1: float}} */
    #[Computed]
    public function resumo(): array
    {
        $hoje   = today()->toDateString();
        $semana = today()->addDays(7)->toDateString();
        $aberto = 'GREATEST(valor_bruto - valor_pago, 0)';

        $r = Transacao::query()
            ->where('tipo', $this->tipo)
            ->whereIn('status', StatusTransacao::abertos())
            ->selectRaw("
                COUNT(*) FILTER (WHERE data_vencimento < ?) AS vencidos_n, COALESCE(SUM({$aberto}) FILTER (WHERE data_vencimento < ?), 0) AS vencidos_v,
                COUNT(*) FILTER (WHERE data_vencimento = ?) AS hoje_n, COALESCE(SUM({$aberto}) FILTER (WHERE data_vencimento = ?), 0) AS hoje_v,
                COUNT(*) FILTER (WHERE data_vencimento > ? AND data_vencimento <= ?) AS semana_n,
                COALESCE(SUM({$aberto}) FILTER (WHERE data_vencimento > ? AND data_vencimento <= ?), 0) AS semana_v,
                COUNT(*) AS abertos_n, COALESCE(SUM({$aberto}), 0) AS abertos_v
            ", [$hoje, $hoje, $hoje, $hoje, $hoje, $semana, $hoje, $semana])
            ->first();

        return [
            'vencidos' => [(int) $r->vencidos_n, (float) $r->vencidos_v],
            'hoje'     => [(int) $r->hoje_n, (float) $r->hoje_v],
            'semana'   => [(int) $r->semana_n, (float) $r->semana_v],
            'abertos'  => [(int) $r->abertos_n, (float) $r->abertos_v],
        ];
    }

    // ─── Listas ─────────────────────────────────────────────────
    private function consultaAbertos(): Builder
    {
        $q = Transacao::query()
            ->select(['id', 'tipo', 'descricao', 'categoria', 'cliente', 'paciente_id', 'fornecedor_id', 'valor_bruto', 'valor_pago',
                'data_competencia', 'data_vencimento', 'forma_pagamento', 'status', 'recorrencia_id'])
            ->with(['paciente:id,nome', 'fornecedor:id,nome'])
            ->where('tipo', $this->tipo)
            ->whereIn('status', StatusTransacao::abertos());

        $hoje = today();
        match ($this->filtro) {
            'vencidos' => $q->where('data_vencimento', '<', $hoje->toDateString()),
            'hoje'     => $q->where('data_vencimento', $hoje->toDateString()),
            'semana'   => $q->whereBetween('data_vencimento', [$hoje->copy()->addDay()->toDateString(), $hoje->copy()->addDays(7)->toDateString()]),
            'mes'      => $q->whereBetween('data_vencimento', [$this->mesRef()->toDateString(), $this->mesRef()->endOfMonth()->toDateString()]),
            default    => null,
        };

        if (($termo = trim($this->busca)) !== '') {
            $like = '%' . mb_strtolower($termo) . '%';
            $q->where(function (Builder $w) use ($like): void {
                $w->whereRaw('LOWER(descricao) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(cliente, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(categoria) LIKE ?', [$like])
                    ->orWhereHas('paciente', fn (Builder $p) => $p->whereRaw('LOWER(nome) LIKE ?', [$like]))
                    ->orWhereHas('fornecedor', fn (Builder $f) => $f->whereRaw('LOWER(nome) LIKE ?', [$like]));
            });
        }

        return $q->orderBy('data_vencimento')->orderBy('created_at');
    }

    private function consultaQuitados(): Builder
    {
        $inicio = $this->mesRef();

        $q = TransacaoBaixa::query()
            ->select(['id', 'transacao_id', 'conta_financeira_id', 'data', 'valor', 'juros', 'multa', 'desconto', 'taxa', 'valor_movimentado', 'forma_pagamento', 'created_at'])
            ->with(['transacao:id,descricao,categoria,cliente,paciente_id,fornecedor_id,status', 'transacao.paciente:id,nome', 'transacao.fornecedor:id,nome', 'conta:id,nome'])
            ->where('tipo', $this->tipo)
            ->whereBetween('data', [$inicio->toDateString(), $inicio->endOfMonth()->toDateString()]);

        if (($termo = trim($this->busca)) !== '') {
            $like = '%' . mb_strtolower($termo) . '%';
            $q->whereHas('transacao', fn (Builder $t) => $t->whereRaw('LOWER(descricao) LIKE ?', [$like])->orWhereRaw('LOWER(categoria) LIKE ?', [$like]));
        }

        return $q->orderByDesc('data')->orderByDesc('created_at');
    }

    #[Computed]
    public function totalQuitadoMes(): float
    {
        $inicio = $this->mesRef();

        return (float) TransacaoBaixa::query()
            ->where('tipo', $this->tipo)
            ->whereBetween('data', [$inicio->toDateString(), $inicio->endOfMonth()->toDateString()])
            ->sum('valor_movimentado');
    }

    /** @return Collection<int, ContaFinanceira> */
    #[Computed]
    public function contas(): Collection
    {
        ContaFinanceira::garantirPadroes();

        return ContaFinanceira::query()->ativas()->select(['id', 'nome', 'tipo', 'padrao'])->orderByDesc('padrao')->orderBy('nome')->limit(50)->get();
    }

    // ─── Baixa ──────────────────────────────────────────────────
    #[Computed]
    public function transacaoBaixa(): ?Transacao
    {
        return $this->baixaId
            ? Transacao::query()->select(['id', 'tipo', 'descricao', 'valor_bruto', 'valor_pago', 'taxa_operacional', 'data_vencimento', 'forma_pagamento', 'status', 'conta_financeira_id'])->find($this->baixaId)
            : null;
    }

    public function abrirBaixa(string $id): void
    {
        $t = Transacao::query()->select(['id', 'tipo', 'valor_bruto', 'valor_pago', 'status', 'forma_pagamento', 'conta_financeira_id'])->findOrFail($id);
        if (! $t->status->emAberto()) {
            $this->flashErro = 'Este lançamento já está quitado ou cancelado.';

            return;
        }

        $this->resetErrorBag();
        $this->detalheId     = null;
        $this->baixaId       = $t->id;
        $this->baixaValor    = Dinheiro::paraCampo($t->valorAberto());
        $this->baixaData     = today()->toDateString();
        $this->baixaConta    = $t->conta_financeira_id ?? ContaFinanceira::sugeridaPara($t->forma_pagamento)?->id ?? '';
        $this->baixaForma    = $t->forma_pagamento?->value ?? '';
        $this->baixaJuros    = '';
        $this->baixaMulta    = '';
        $this->baixaDesconto = '';
        $this->baixaObs      = '';
        $this->baixaEncargos = false;
        $this->baixaCartao   = 'padrao';
        unset($this->transacaoBaixa);
    }

    public function fecharBaixa(): void
    {
        $this->baixaId = null;
        $this->resetErrorBag();
    }

    public function updatedBaixaForma(): void
    {
        $sugerida = ContaFinanceira::sugeridaPara(FormaPagamento::tryFrom($this->baixaForma));
        if ($sugerida) {
            $this->baixaConta = $sugerida->id;
        }
    }

    /** Recebimento no crédito numa conta Maquininha: pergunta se antecipa ou recebe mês a mês. */
    public function perguntaAntecipar(): ?ContaFinanceira
    {
        if ($this->aPagar() || ! str_starts_with($this->baixaForma, 'credito')) {
            return null;
        }
        $conta = $this->contas->firstWhere('id', $this->baixaConta);

        return $conta?->tipo === \App\Enums\TipoContaFinanceira::Maquininha
            ? ContaFinanceira::query()->select(['id', 'antecipar_padrao', 'taxa_antecipacao_mes', 'prazo_credito_dias', 'antecipacao_dias'])->find($conta->id)
            : null;
    }

    /** Total que sai/entra da conta, para conferência no formulário. */
    public function totalBaixa(): float
    {
        return round(Dinheiro::numero($this->baixaValor) + Dinheiro::numero($this->baixaJuros) + Dinheiro::numero($this->baixaMulta) - Dinheiro::numero($this->baixaDesconto), 2);
    }

    public function confirmarBaixa(BaixarTransacaoAction $baixar): void
    {
        $this->validate([
            'baixaValor'    => ['required', 'regex:/^[\d.,]+$/'],
            'baixaData'     => ['required', 'date', 'before_or_equal:' . today()->addYear()->toDateString()],
            'baixaConta'    => ['required', 'uuid', Rule::exists('contas_financeiras', 'id')->where('tenant_id', app(\App\Support\ClinicaAtual::class)->id())],
            'baixaForma'    => ['nullable', Rule::enum(FormaPagamento::class)],
            'baixaJuros'    => ['nullable', 'regex:/^[\d.,]+$/'],
            'baixaMulta'    => ['nullable', 'regex:/^[\d.,]+$/'],
            'baixaDesconto' => ['nullable', 'regex:/^[\d.,]+$/'],
            'baixaObs'      => ['nullable', 'string', 'max:255'],
        ], [
            'baixaValor.required' => 'Informe o valor.',
            'baixaValor.regex'    => 'Valor inválido.',
            'baixaConta.required' => 'Escolha a conta.',
            'baixaData.required'  => 'Informe a data.',
            '*.regex'             => 'Valor inválido.',
        ]);

        try {
            $t = Transacao::query()->findOrFail($this->baixaId);
            $baixar->execute($t, [
                'valor'               => Dinheiro::numero($this->baixaValor),
                'data'                => $this->baixaData,
                'conta_financeira_id' => $this->baixaConta,
                'forma_pagamento'     => $this->baixaForma ?: null,
                'juros'               => Dinheiro::numero($this->baixaJuros),
                'multa'               => Dinheiro::numero($this->baixaMulta),
                'desconto'            => Dinheiro::numero($this->baixaDesconto),
                'observacoes'         => $this->baixaObs ?: null,
                'antecipar'           => $this->baixaCartao === 'padrao' ? null : $this->baixaCartao === 'antecipado',
            ]);
            $t->refresh();
            $this->flashSucesso = match (true) {
                $t->status === StatusTransacao::Pago => $this->aPagar() ? 'Pagamento registrado. Conta quitada.' : 'Recebimento registrado. Conta quitada.',
                default => 'Pagamento parcial registrado. Ainda falta R$ ' . number_format($t->valorAberto(), 2, ',', '.') . '.',
            };
            $this->baixaId = null;
            unset($this->resumo, $this->totalQuitadoMes);
        } catch (Throwable $e) {
            $this->addError('baixaValor', $this->mensagemDeErro($e, 'Não foi possível registrar'));
        }
    }

    // ─── Detalhe / estorno ──────────────────────────────────────
    #[Computed]
    public function detalhe(): ?Transacao
    {
        return $this->detalheId
            ? Transacao::query()
                ->with(['paciente:id,nome', 'fornecedor:id,nome', 'baixas' => fn ($q) => $q->with(['conta:id,nome', 'usuario:id,name'])->orderBy('data')->orderBy('created_at')])
                ->find($this->detalheId)
            : null;
    }

    public function abrirDetalhe(string $id): void
    {
        $this->detalheId = $id;
        unset($this->detalhe);
    }

    public function fecharDetalhe(): void
    {
        $this->detalheId = null;
    }

    public function estornar(string $baixaId, EstornarBaixaAction $estornar): void
    {
        try {
            $baixa = TransacaoBaixa::query()->findOrFail($baixaId);
            $estornar->execute($baixa);
            $this->flashSucesso = $this->aPagar() ? 'Pagamento desfeito. A conta voltou para o a pagar.' : 'Recebimento desfeito. A conta voltou para o a receber.';
            unset($this->detalhe, $this->resumo, $this->totalQuitadoMes);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível desfazer');
        }
    }

    public function render(): View
    {
        $quitados = $this->filtro === 'quitados';

        return view('livewire.contas-pagar-receber', [
            'aPagar'   => $this->aPagar(),
            'lista'    => $quitados ? $this->consultaQuitados()->paginate(20) : $this->consultaAbertos()->paginate(20),
            'quitados' => $quitados,
            'mesRef'   => $this->mesRef(),
            'formas'   => FormaPagamento::cases(),
        ])->layout('layouts.app', ['title' => $this->aPagar() ? 'Contas a pagar' : 'Contas a receber']);
    }
}
