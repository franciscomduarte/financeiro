<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\NotaFiscal\AtualizarSituacaoNotaFiscalAction;
use App\Actions\NotaFiscal\CancelarNotaFiscalAction;
use App\Actions\NotaFiscal\EmitirNotaFiscalAction;
use App\Enums\StatusNotaFiscal;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\NotaFiscal;
use App\Models\Transacao;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/** Notas fiscais de serviço (NFS-e): emissão a partir das receitas, acompanhamento e cancelamento. */
class NotaFiscalIndex extends Component
{
    use Concerns\CamposEndereco;
    use Concerns\MensagemDeErro;
    use WithPagination;

    #[Url(as: 'situacao', except: '')]
    public string $filtroStatus = '';

    // ─── Emissão ────────────────────────────────────────────────
    public bool $modalEmitir = false;
    public string $buscaReceita = '';
    public string $transacaoId = '';
    public string $tomadorNome = '';
    public string $tomadorCpf = '';
    public string $tomadorEmail = '';
    public string $discriminacao = '';

    // ─── Cancelamento ───────────────────────────────────────────
    public ?string $cancelandoId = null;
    public string $justificativa = '';

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        if ($id = request()->query('transacao')) {
            $this->abrirEmissao();
            $this->escolherReceita((string) $id);
        }
    }

    public function updatingFiltroStatus(): void
    {
        $this->resetPage();
    }

    /** Receitas recentes ainda sem nota (emitida ou em processamento). */
    #[Computed]
    public function receitas(): Collection
    {
        $termo = trim($this->buscaReceita);

        return Transacao::query()
            ->select(['id', 'descricao', 'cliente', 'valor_bruto', 'data_competencia', 'paciente_id'])
            ->where('tipo', TipoTransacao::Entrada)
            ->where('status', '!=', StatusTransacao::Cancelado)
            ->where('data_competencia', '>=', now()->subDays(120)->toDateString())
            ->whereDoesntHave('notasFiscais', fn ($q) => $q->whereIn('status', [StatusNotaFiscal::Processando, StatusNotaFiscal::Autorizada]))
            ->when($termo !== '', fn ($q) => $q->where(fn ($w) => $w->where('descricao', 'ilike', "%{$termo}%")->orWhere('cliente', 'ilike', "%{$termo}%")))
            ->orderByDesc('data_competencia')
            ->limit(30)
            ->get();
    }

    #[Computed]
    public function receitaEscolhida(): ?Transacao
    {
        return $this->transacaoId !== ''
            ? Transacao::query()->select(['id', 'descricao', 'valor_bruto', 'data_competencia'])->find($this->transacaoId)
            : null;
    }

    public function abrirEmissao(): void
    {
        $this->flashSucesso = $this->flashErro = null;
        $this->resetValidation();
        $this->reset('buscaReceita', 'transacaoId', 'tomadorNome', 'tomadorCpf', 'tomadorEmail', 'discriminacao');
        $this->resetEndereco();
        $this->modalEmitir = true;
    }

    public function reemitir(string $transacaoId): void
    {
        $this->abrirEmissao();
        $this->escolherReceita($transacaoId);
    }

    /** Preenche o tomador com o paciente da receita e monta a descrição do serviço. */
    public function escolherReceita(string $id): void
    {
        $t = Transacao::query()->with('paciente:id,nome,cpf,email,cep,logradouro,numero,complemento,bairro,cidade,uf,codigo_municipio')
            ->select(['id', 'descricao', 'cliente', 'paciente_id', 'tipo'])
            ->where('tipo', TipoTransacao::Entrada)->find($id);
        if ($t === null) {
            $this->flashErro = 'Lançamento não encontrado ou não é uma receita.';

            return;
        }

        $padrao = (string) app(ClinicaAtual::class)->get()?->nfse_discriminacao_padrao;

        $this->transacaoId   = $t->id;
        $this->tomadorNome   = (string) ($t->paciente?->nome ?? $t->cliente);
        $this->tomadorCpf    = (string) $t->paciente?->cpf;
        $this->tomadorEmail  = (string) $t->paciente?->email;
        $this->preencherEndereco($t->paciente);
        $this->discriminacao = trim($padrao . "\n" . $t->descricao);
        unset($this->receitaEscolhida);
    }

    public function emitir(EmitirNotaFiscalAction $emitir): void
    {
        $this->flashSucesso = $this->flashErro = null;
        $this->validate([
            'transacaoId'   => ['required', 'uuid'],
            'tomadorNome'   => ['required', 'string', 'max:150'],
            'tomadorCpf'    => ['nullable', 'string', 'max:18'],
            'tomadorEmail'  => ['nullable', 'email', 'max:150'],
            'discriminacao' => ['required', 'string', 'min:5', 'max:2000'],
            ...$this->regrasEndereco(),
        ], [
            ...$this->mensagensEndereco(),
            'transacaoId.required'   => 'Escolha a receita.',
            'tomadorNome.required'   => 'Informe o nome de quem recebe a nota.',
            'discriminacao.required' => 'Descreva o serviço prestado.',
        ]);

        try {
            $emitir->execute($this->transacaoId, [
                'discriminacao' => $this->discriminacao,
                'tomador_nome'  => $this->tomadorNome,
                'tomador_cpf'   => $this->tomadorCpf,
                'tomador_email' => $this->tomadorEmail,
                'tomador_endereco' => $this->dadosEndereco(),
            ]);
            $this->modalEmitir  = false;
            $this->flashSucesso = 'Nota enviada para a prefeitura. Em instantes a situação muda para "Emitida".';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível emitir a nota');
        }
    }

    public function atualizar(string $id, AtualizarSituacaoNotaFiscalAction $atualizar): void
    {
        $this->flashSucesso = $this->flashErro = null;

        try {
            $nota = $atualizar->execute(NotaFiscal::query()->findOrFail($id));
            $this->flashSucesso = 'Situação atualizada: ' . mb_strtolower($nota->status->label()) . '.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível consultar a nota');
        }
    }

    public function abrirCancelamento(string $id): void
    {
        $this->resetValidation();
        $this->cancelandoId  = $id;
        $this->justificativa = '';
    }

    public function cancelar(CancelarNotaFiscalAction $cancelar): void
    {
        $this->flashSucesso = $this->flashErro = null;

        try {
            $cancelar->execute((string) $this->cancelandoId, $this->justificativa);
            $this->cancelandoId = null;
            $this->flashSucesso = 'Nota cancelada na prefeitura.';
        } catch (Throwable $e) {
            $this->addError('justificativa', $this->mensagemDeErro($e, 'Não foi possível cancelar a nota'));
        }
    }

    public function render(): View
    {
        $notas = NotaFiscal::query()
            ->select(['id', 'transacao_id', 'status', 'homologacao', 'valor', 'tomador_nome', 'numero', 'url', 'url_xml', 'mensagem_erro', 'created_at', 'autorizada_em'])
            ->when(StatusNotaFiscal::tryFrom($this->filtroStatus), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        $inicioMes = now()->startOfMonth();

        return view('livewire.nota-fiscal-index', [
            'notas'      => $notas,
            'status'     => StatusNotaFiscal::cases(),
            'pendencias' => app(ClinicaAtual::class)->get()?->pendenciasNfse() ?? [],
            'homologacao' => (bool) app(ClinicaAtual::class)->get()?->nfse_homologacao,
            'resumo'     => [
                'emitidas_mes' => (float) NotaFiscal::query()->where('status', StatusNotaFiscal::Autorizada)->where('autorizada_em', '>=', $inicioMes)->sum('valor'),
                'qtd_mes'      => NotaFiscal::query()->where('status', StatusNotaFiscal::Autorizada)->where('autorizada_em', '>=', $inicioMes)->count(),
                'processando'  => NotaFiscal::query()->where('status', StatusNotaFiscal::Processando)->count(),
                'erros'        => NotaFiscal::query()->where('status', StatusNotaFiscal::Erro)->where('created_at', '>=', now()->subDays(30))->count(),
            ],
        ])->layout('layouts.app', ['title' => 'Notas fiscais']);
    }
}
