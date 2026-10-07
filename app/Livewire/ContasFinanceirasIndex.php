<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Financeiro\AjustarSaldoContaAction;
use App\Actions\Financeiro\TransferirEntreContasAction;
use App\Enums\TipoContaFinanceira;
use App\Enums\TipoTransacao;
use App\Models\ContaFinanceira;
use App\Models\TransacaoBaixa;
use App\Models\Transferencia;
use App\Services\SaldoContasService;
use App\Support\ClinicaAtual;
use App\Support\Dinheiro;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/** Caixa e bancos: saldo de cada conta, extrato do mês, transferências e ajuste de saldo. */
class ContasFinanceirasIndex extends Component
{
    use Concerns\MensagemDeErro;

    private const LIMITE_EXTRATO = 500;

    #[Url(as: 'conta', except: '')]
    public string $contaId = '';

    #[Url]
    public string $mes = '';

    // ─── Conta (criar/editar) ───────────────────────────────────
    public bool $modalConta      = false;
    public ?string $editandoId   = null;
    public string $nome          = '';
    public string $tipoConta     = 'banco';
    public string $banco         = '';
    public string $agencia       = '';
    public string $numero        = '';
    public bool $padrao          = false;
    public bool $ativa           = true;

    // ─── Transferência ──────────────────────────────────────────
    public bool $modalTransferencia = false;
    public string $trOrigem   = '';
    public string $trDestino  = '';
    public string $trData     = '';
    public string $trValor    = '';
    public string $trDescricao = '';

    // ─── Ajuste de saldo ────────────────────────────────────────
    public ?string $ajusteId  = null;
    public string $ajusteSaldo = '';
    public string $ajusteData  = '';

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        ContaFinanceira::garantirPadroes();
        if (! preg_match('/^\d{4}-\d{2}$/', $this->mes)) {
            $this->mes = today()->format('Y-m');
        }
    }

    private function mesRef(): CarbonImmutable
    {
        return $this->mes !== '' ? CarbonImmutable::createFromFormat('!Y-m', $this->mes) : CarbonImmutable::today()->startOfMonth();
    }

    private function tenantId(): ?string
    {
        return app(ClinicaAtual::class)->id();
    }

    /** @return Collection<int, ContaFinanceira> */
    #[Computed]
    public function contas(): Collection
    {
        return ContaFinanceira::query()
            ->select(['id', 'nome', 'tipo', 'banco', 'agencia', 'numero', 'saldo_inicial', 'saldo_inicial_em', 'padrao', 'ativa'])
            ->orderByDesc('ativa')->orderByDesc('padrao')->orderBy('nome')
            ->limit(50)
            ->get();
    }

    /** @return Collection<string, float> */
    #[Computed]
    public function saldos(): Collection
    {
        return app(SaldoContasService::class)->saldos();
    }

    // ─── Extrato ────────────────────────────────────────────────
    public function verExtrato(string $id): void
    {
        $this->contaId = $id;
        unset($this->extrato);
    }

    public function fecharExtrato(): void
    {
        $this->contaId = '';
    }

    /**
     * Movimentos da conta no mês, com saldo após cada um.
     *
     * @return array{conta: ?ContaFinanceira, anterior: float, final: float, entradas: float, saidas: float,
     *               linhas: array<int, array{data: \Carbon\CarbonInterface, descricao: string, detalhe: ?string, valor: float, saldo: float, transferencia_id: ?string}>}
     */
    #[Computed]
    public function extrato(): array
    {
        $conta = $this->contaId !== '' ? $this->contas->firstWhere('id', $this->contaId) : null;
        if ($conta === null) {
            return ['conta' => null, 'anterior' => 0, 'final' => 0, 'entradas' => 0, 'saidas' => 0, 'linhas' => []];
        }

        $inicio  = $this->mesRef();
        $fim     = $inicio->endOfMonth();
        $desde   = $conta->saldo_inicial_em && $conta->saldo_inicial_em->gte($inicio) ? $conta->saldo_inicial_em->toImmutable()->addDay() : $inicio;
        $saldos  = app(SaldoContasService::class);
        $anterior = $conta->saldo_inicial_em && $conta->saldo_inicial_em->gte($inicio)
            ? (float) $conta->saldo_inicial
            : $saldos->saldo($conta, $inicio->subDay());

        $linhas = collect();

        TransacaoBaixa::query()
            ->select(['id', 'transacao_id', 'tipo', 'data', 'valor_movimentado', 'juros', 'multa', 'desconto', 'taxa', 'created_at'])
            ->with(['transacao:id,descricao,categoria,cliente,paciente_id,fornecedor_id', 'transacao.paciente:id,nome', 'transacao.fornecedor:id,nome'])
            ->where('conta_financeira_id', $conta->id)
            ->whereBetween('data', [$desde->toDateString(), $fim->toDateString()])
            ->orderBy('data')->orderBy('created_at')
            ->limit(self::LIMITE_EXTRATO)
            ->get()
            ->each(function (TransacaoBaixa $b) use ($linhas): void {
                $t = $b->transacao;
                $linhas->push([
                    'data'             => $b->data,
                    'ordem'            => $b->created_at?->getTimestamp() ?? 0,
                    'descricao'        => $t?->descricao ?? 'Lançamento',
                    'detalhe'          => collect([$t?->categoria, $t?->fornecedor?->nome ?? $t?->paciente?->nome ?? $t?->cliente])->filter()->implode(' · ') ?: null,
                    'valor'            => ($b->tipo === TipoTransacao::Entrada ? 1 : -1) * (float) $b->valor_movimentado,
                    'transferencia_id' => null,
                ]);
            });

        Transferencia::query()
            ->select(['id', 'conta_origem_id', 'conta_destino_id', 'data', 'valor', 'descricao', 'created_at'])
            ->with(['origem:id,nome', 'destino:id,nome'])
            ->where(fn ($q) => $q->where('conta_origem_id', $conta->id)->orWhere('conta_destino_id', $conta->id))
            ->whereBetween('data', [$desde->toDateString(), $fim->toDateString()])
            ->orderBy('data')
            ->limit(self::LIMITE_EXTRATO)
            ->get()
            ->each(function (Transferencia $tr) use ($linhas, $conta): void {
                $saida = $tr->conta_origem_id === $conta->id;
                $linhas->push([
                    'data'             => $tr->data,
                    'ordem'            => $tr->created_at?->getTimestamp() ?? 0,
                    'descricao'        => $saida ? 'Transferência para ' . $tr->destino?->nome : 'Transferência de ' . $tr->origem?->nome,
                    'detalhe'          => $tr->descricao,
                    'valor'            => ($saida ? -1 : 1) * (float) $tr->valor,
                    'transferencia_id' => $tr->id,
                ]);
            });

        $saldo  = $anterior;
        $linhas = $linhas->sortBy([['data', 'asc'], ['ordem', 'asc']])->values()->map(function (array $l) use (&$saldo): array {
            $saldo += $l['valor'];

            return $l + ['saldo' => round($saldo, 2)];
        });

        return [
            'conta'    => $conta,
            'anterior' => round($anterior, 2),
            'final'    => round($saldo, 2),
            'entradas' => round((float) $linhas->where('valor', '>', 0)->sum('valor'), 2),
            'saidas'   => round((float) -$linhas->where('valor', '<', 0)->sum('valor'), 2),
            'linhas'   => $linhas->all(),
        ];
    }

    // ─── Conta ──────────────────────────────────────────────────
    public function novaConta(): void
    {
        $this->resetErrorBag();
        $this->editandoId = null;
        $this->nome = $this->banco = $this->agencia = $this->numero = '';
        $this->tipoConta = TipoContaFinanceira::Banco->value;
        $this->padrao = false;
        $this->ativa  = true;
        $this->modalConta = true;
    }

    public function editarConta(string $id): void
    {
        $c = ContaFinanceira::query()->findOrFail($id);
        $this->resetErrorBag();
        $this->editandoId = $c->id;
        $this->nome       = $c->nome;
        $this->tipoConta  = $c->tipo->value;
        $this->banco      = (string) $c->banco;
        $this->agencia    = (string) $c->agencia;
        $this->numero     = (string) $c->numero;
        $this->padrao     = $c->padrao;
        $this->ativa      = $c->ativa;
        $this->modalConta = true;
    }

    public function salvarConta(): void
    {
        $this->validate([
            'nome'      => ['required', 'string', 'max:80', Rule::unique('contas_financeiras', 'nome')->where('tenant_id', $this->tenantId())->ignore($this->editandoId)],
            'tipoConta' => ['required', Rule::enum(TipoContaFinanceira::class)],
            'banco'     => ['nullable', 'string', 'max:80'],
            'agencia'   => ['nullable', 'string', 'max:20'],
            'numero'    => ['nullable', 'string', 'max:30'],
            'padrao'    => ['boolean'],
            'ativa'     => ['boolean'],
        ], ['nome.required' => 'Dê um nome à conta.', 'nome.unique' => 'Já existe uma conta com esse nome.']);

        try {
            DB::transaction(function (): void {
                $dados = [
                    'nome' => trim($this->nome), 'tipo' => $this->tipoConta, 'banco' => $this->banco ?: null,
                    'agencia' => $this->agencia ?: null, 'numero' => $this->numero ?: null,
                    'padrao' => $this->padrao && $this->ativa, 'ativa' => $this->ativa,
                ];
                $conta = $this->editandoId
                    ? tap(ContaFinanceira::query()->findOrFail($this->editandoId))->update($dados)
                    : ContaFinanceira::query()->create($dados + ['saldo_inicial' => 0]);
                if ($conta->padrao) {
                    ContaFinanceira::query()->whereKeyNot($conta->id)->where('padrao', true)->update(['padrao' => false]);
                }
            });
            $this->flashSucesso = $this->editandoId ? 'Conta atualizada.' : 'Conta criada. Informe o saldo atual em "Ajustar saldo".';
            $this->modalConta = false;
            unset($this->contas, $this->saldos);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar a conta');
        }
    }

    // ─── Transferência ──────────────────────────────────────────
    public function novaTransferencia(?string $origem = null): void
    {
        $this->resetErrorBag();
        $ativas = $this->contas->where('ativa', true)->values();
        $this->trOrigem    = $origem ?? (string) ($ativas->firstWhere('tipo', TipoContaFinanceira::Maquininha)?->id ?? $ativas->first()?->id);
        $this->trDestino   = (string) ($ativas->firstWhere('padrao', true)?->id ?? $ativas->firstWhere('id', '!=', $this->trOrigem)?->id);
        if ($this->trDestino === $this->trOrigem) {
            $this->trDestino = (string) $ativas->firstWhere('id', '!=', $this->trOrigem)?->id;
        }
        $this->trData      = today()->toDateString();
        $this->trValor     = '';
        $this->trDescricao = '';
        $this->modalTransferencia = true;
    }

    public function confirmarTransferencia(TransferirEntreContasAction $transferir): void
    {
        $existe = Rule::exists('contas_financeiras', 'id')->where('tenant_id', $this->tenantId());
        $this->validate([
            'trOrigem'    => ['required', 'uuid', $existe],
            'trDestino'   => ['required', 'uuid', 'different:trOrigem', $existe],
            'trData'      => ['required', 'date'],
            'trValor'     => ['required', 'regex:/^[\d.,]+$/'],
            'trDescricao' => ['nullable', 'string', 'max:255'],
        ], ['trDestino.different' => 'Escolha uma conta diferente da origem.', 'trValor.required' => 'Informe o valor.', 'trValor.regex' => 'Valor inválido.']);

        try {
            $transferir->execute([
                'conta_origem_id' => $this->trOrigem, 'conta_destino_id' => $this->trDestino, 'data' => $this->trData,
                'valor' => Dinheiro::numero($this->trValor), 'descricao' => $this->trDescricao ?: null,
            ]);
            $this->flashSucesso = 'Transferência registrada.';
            $this->modalTransferencia = false;
            unset($this->saldos, $this->extrato);
        } catch (Throwable $e) {
            $this->addError('trValor', $this->mensagemDeErro($e, 'Não foi possível transferir'));
        }
    }

    public function excluirTransferencia(string $id): void
    {
        try {
            DB::transaction(fn () => Transferencia::query()->findOrFail($id)->delete());
            $this->flashSucesso = 'Transferência excluída.';
            unset($this->saldos, $this->extrato);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível excluir');
        }
    }

    // ─── Ajuste de saldo ────────────────────────────────────────
    public function abrirAjuste(string $id): void
    {
        $this->resetErrorBag();
        $this->ajusteId    = $id;
        $this->ajusteSaldo = Dinheiro::paraCampo((float) ($this->saldos[$id] ?? 0));
        $this->ajusteData  = today()->toDateString();
    }

    public function confirmarAjuste(AjustarSaldoContaAction $ajustar): void
    {
        $this->validate([
            'ajusteSaldo' => ['required', 'regex:/^-?[\d.,]+$/'],
            'ajusteData'  => ['required', 'date', 'before_or_equal:today'],
        ], ['ajusteSaldo.required' => 'Informe o saldo.', 'ajusteSaldo.regex' => 'Valor inválido.', 'ajusteData.before_or_equal' => 'Use a data de hoje ou anterior.']);

        try {
            $conta = ContaFinanceira::query()->findOrFail($this->ajusteId);
            $saldo = str_starts_with(trim($this->ajusteSaldo), '-') ? -Dinheiro::numero(ltrim(trim($this->ajusteSaldo), '-')) : Dinheiro::numero($this->ajusteSaldo);
            $ajustar->execute($conta, $saldo, $this->ajusteData);
            $this->flashSucesso = 'Saldo ajustado. Os movimentos a partir do dia seguinte entram nesse saldo.';
            $this->ajusteId = null;
            unset($this->contas, $this->saldos, $this->extrato);
        } catch (Throwable $e) {
            $this->addError('ajusteSaldo', $this->mensagemDeErro($e, 'Não foi possível ajustar'));
        }
    }

    public function render(): View
    {
        $ativas = $this->contas->where('ativa', true);

        return view('livewire.contas-financeiras-index', [
            'tipos'      => TipoContaFinanceira::cases(),
            'totalGeral' => round((float) $ativas->sum(fn ($c) => $this->saldos[$c->id] ?? 0), 2),
            'mesRef'     => $this->mesRef(),
        ])->layout('layouts.app', ['title' => 'Caixa e bancos']);
    }
}
