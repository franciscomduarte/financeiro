<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CalcularValoresTransacaoAction;
use App\Actions\CreateTransacaoAction;
use App\Actions\EnviarAnexoAction;
use App\Actions\UpdateTransacaoAction;
use App\Actions\UploadAnexoAction;
use App\Enums\FaseTransacao;
use App\Enums\FormaPagamento;
use App\Enums\RecorrenciaTransacao;
use App\Enums\StatusTransacao;
use App\Enums\TipoAnexo;
use App\Enums\TipoTransacao;
use App\Models\Paciente;
use App\Models\Transacao;
use App\Models\TransacaoAnexo;
use RuntimeException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class TransacaoIndex extends Component
{
    use Concerns\MensagemDeErro;
    use WithPagination;
    use WithFileUploads;

    // ─── Filtros ────────────────────────────────────────────────
    public string $filtroTipo       = '';
    public string $filtroFase       = '';
    public string $filtroStatus     = '';
    public string $filtroCategoria  = '';
    public string $periodoInicio    = '';
    public string $periodoFim       = '';

    // ─── Estado dos modais ──────────────────────────────────────
    public bool $modalCriar        = false;
    public bool $modalEditar       = false;
    public bool $modalDetalhe      = false;
    public bool $modalEnviarAnexo  = false;

    public ?string $transacaoEditandoId  = null;
    public ?string $transacaoDetalheId   = null;

    // ─── Envio de anexo ─────────────────────────────────────────
    public ?string $anexoEnviarId   = null;
    public ?string $anexoEnviarNome = null;
    public bool    $enviarEmail     = true;
    public bool    $enviarWhatsapp  = true;

    // ─── Formulário ─────────────────────────────────────────────
    public string $tipo             = 'entrada';
    public string $fase             = 'operacao';
    public string $categoria        = '';
    public string $descricao        = '';
    public string $pacienteId       = '';
    public string $pacienteBusca    = '';
    public string $valorBruto       = '';
    public string $formaPagamento   = 'pix';
    public string $dataCompetencia  = '';
    public string $dataPagamento    = '';
    public string $dataVencimento   = '';
    public string $contaFinanceiraId = '';
    public string $recebimentoCartao = 'padrao'; // padrao | parcelado | antecipado
    public string $status           = 'pendente';
    public string $recorrencia      = 'unica';
    public string $recorrenciaAte   = '';
    public bool   $recorrenciaPago  = false;
    public string $observacoes      = '';
    /** Lançamento em edição veio de uma recorrência (mostra aviso no formulário) */
    public bool   $editandoRecorrente = false;

    // ─── Valores calculados ─────────────────────────────────────
    public ?float $taxaOperacional = null;
    public ?float $impostoEstimado = null;
    public ?float $valorLiquido    = null;

    // ─── Upload de anexos ────────────────────────────────────────
    /** @var mixed */
    public $arquivoBoleto      = null;
    /** @var mixed */
    public $arquivoComprovante = null;

    // ─── Flash interno ──────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    protected function queryString(): array
    {
        return [
            'filtroTipo'       => ['as' => 'tipo',    'except' => ''],
            'filtroFase'       => ['as' => 'fase',    'except' => ''],
            'filtroStatus'     => ['as' => 'status',  'except' => ''],
            'filtroCategoria'  => ['as' => 'cat',     'except' => ''],
            'periodoInicio'    => ['as' => 'de',      'except' => ''],
            'periodoFim'       => ['as' => 'ate',     'except' => ''],
        ];
    }

    // ─── Categorias ─────────────────────────────────────────────
    public function getCategoriasEntradaProperty(): array
    {
        return \App\Models\PlanoConta::nomes('entrada');
    }

    public function getCategoriasSaidaProperty(): array
    {
        return \App\Models\PlanoConta::nomes('saida');
    }

    #[Computed]
    public function categorias(): array
    {
        return $this->tipo === 'entrada'
            ? $this->categoriasEntrada
            : $this->categoriasSaida;
    }

    // ─── Lifecycle ──────────────────────────────────────────────
    public function updatedFiltroTipo(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroFase(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroCategoria(): void
    {
        $this->resetPage();
    }

    public function updatedPeriodoInicio(): void
    {
        $this->resetPage();
    }

    public function updatedPeriodoFim(): void
    {
        $this->resetPage();
    }

    public function updatedValorBruto(): void
    {
        $this->recalcularValores();
    }

    public function updatedFormaPagamento(): void
    {
        $this->recalcularValores();
    }

    public function updatedStatus(): void
    {
        if ($this->status === StatusTransacao::Pago->value && $this->dataPagamento === '') {
            $this->dataPagamento = now()->toDateString();
        }
    }

    public function updatedTipo(): void
    {
        $this->categoria = '';
        $this->recalcularValores();
    }

    // ─── Recalcular ─────────────────────────────────────────────
    private function recalcularValores(): void
    {
        $valorBruto = (float) str_replace(',', '.', $this->valorBruto);

        if ($valorBruto <= 0 || $this->formaPagamento === '') {
            $this->taxaOperacional = null;
            $this->impostoEstimado = null;
            $this->valorLiquido    = null;

            return;
        }

        try {
            $formaPagamentoEnum = FormaPagamento::tryFrom($this->formaPagamento);
            $tipoEnum           = TipoTransacao::tryFrom($this->tipo);

            if ($formaPagamentoEnum === null || $tipoEnum === null) {
                return;
            }

            $action  = app(CalcularValoresTransacaoAction::class);
            $valores = $action->execute($valorBruto, $formaPagamentoEnum, $tipoEnum);

            $this->taxaOperacional = $valores['taxa_operacional'];
            $this->impostoEstimado = $valores['imposto_estimado'];
            $this->valorLiquido    = $valores['valor_liquido'];
        } catch (Throwable $e) {
            Log::error('Erro ao recalcular valores', ['error' => $e->getMessage()]);
        }
    }

    // ─── Modais ─────────────────────────────────────────────────
    public function mount(): void
    {
        // Atalho da aba Recorrências: abre o novo lançamento já repetindo todo mês
        if (request()->query('novo') === 'recorrente') {
            $this->abrirModalCriar();
            $this->recorrencia = RecorrenciaTransacao::Mensal->value;
            $this->tipo        = 'saida';
        }

        // Atalhos de Contas a pagar/receber: novo lançamento do tipo certo ou edição direta
        if (in_array(request()->query('novo'), ['saida', 'entrada'], true)) {
            $this->abrirModalCriar();
            $this->tipo = (string) request()->query('novo');
        }
        $editar = request()->query('editar');
        if (is_string($editar) && \Illuminate\Support\Str::isUuid($editar) && Transacao::query()->whereKey($editar)->exists()) {
            $this->abrirModalEditar($editar);
        }
    }

    public function abrirModalCriar(): void
    {
        $this->resetFormulario();
        $this->dataCompetencia = now()->format('Y-m-d');
        $this->modalCriar      = true;
    }

    public function fecharModalCriar(): void
    {
        $this->modalCriar = false;
        $this->resetFormulario();
    }

    public function abrirModalEditar(string $id): void
    {
        $transacao = Transacao::with(['anexos', 'paciente:id,nome'])->findOrFail($id);

        $this->transacaoEditandoId = $id;
        $this->tipo                = $transacao->tipo->value;
        $this->fase                = $transacao->fase->value;
        $this->categoria           = $transacao->categoria;
        $this->descricao           = $transacao->descricao;
        $this->pacienteId          = $transacao->paciente_id ?? '';
        $this->pacienteBusca       = $transacao->paciente?->nome ?? '';
        $this->valorBruto          = (string) $transacao->valor_bruto;
        $this->formaPagamento      = $transacao->forma_pagamento->value;
        $this->dataCompetencia     = $transacao->data_competencia?->format('Y-m-d') ?? '';
        $this->dataPagamento       = $transacao->data_pagamento?->format('Y-m-d') ?? '';
        $this->dataVencimento      = $transacao->data_vencimento?->format('Y-m-d') ?? '';
        $this->contaFinanceiraId   = $transacao->conta_financeira_id ?? '';
        $this->status              = $transacao->status->value;
        $this->recorrencia         = $transacao->recorrencia->value;
        $this->editandoRecorrente  = $transacao->recorrencia_id !== null;
        $this->observacoes         = $transacao->observacoes ?? '';

        $this->taxaOperacional = (float) $transacao->taxa_operacional;
        $this->impostoEstimado = (float) $transacao->imposto_estimado;
        $this->valorLiquido    = (float) $transacao->valor_liquido;

        $this->modalEditar = true;
    }

    public function fecharModalEditar(): void
    {
        $this->modalEditar         = false;
        $this->transacaoEditandoId = null;
        $this->resetFormulario();
    }

    public function abrirModalDetalhe(string $id): void
    {
        $this->transacaoDetalheId = $id;
        $this->modalDetalhe       = true;
    }

    public function fecharModalDetalhe(): void
    {
        $this->modalDetalhe       = false;
        $this->transacaoDetalheId = null;
        $this->arquivoBoleto      = null;
        $this->arquivoComprovante = null;
        $this->modalEnviarAnexo   = false;
        $this->anexoEnviarId      = null;
        $this->anexoEnviarNome    = null;
    }

    public function abrirModalEnviarAnexo(string $anexoId): void
    {
        $this->anexoEnviarId    = $anexoId;
        $this->enviarEmail      = true;
        $this->enviarWhatsapp   = true;
        $this->anexoEnviarNome  = $this->transacaoDetalhe?->anexos->firstWhere('id', $anexoId)?->nome_arquivo ?? 'Documento';
        $this->modalEnviarAnexo = true;
    }

    public function confirmarEnviarAnexo(EnviarAnexoAction $action): void
    {
        $anexo    = TransacaoAnexo::with('transacao.paciente')->findOrFail($this->anexoEnviarId);
        $paciente = $anexo->transacao?->paciente;

        $this->modalEnviarAnexo = false;

        if (! $paciente) {
            $this->flashErro = 'Este lançamento não tem paciente vinculado. Vincule um paciente para enviar o documento.';
            return;
        }

        try {
            $result = $action->execute($anexo, $paciente, $this->enviarEmail, $this->enviarWhatsapp);

            $sucessos = array_filter([
                $result['email']     === true ? 'e-mail'    : null,
                $result['whatsapp']  === true ? 'WhatsApp'  : null,
            ]);
            $falhas = array_filter([
                $result['email']     === false ? 'e-mail'   : null,
                $result['whatsapp']  === false ? 'WhatsApp' : null,
            ]);

            if (! empty($sucessos)) {
                $msg = 'Documento enviado via ' . implode(' e ', $sucessos) . '.';
                if (! empty($falhas)) {
                    $msg .= ' Não foi possível enviar por ' . implode(' e ', $falhas) . '.';
                }
                $this->flashSucesso = $msg;
            } else {
                $this->flashErro = 'Não foi possível enviar. Confira o e-mail e o telefone do paciente e tente de novo.';
            }
        } catch (RuntimeException $e) {
            $this->flashErro = $this->mensagemDeErro($e);
        } catch (\Throwable $e) {
            Log::error('Erro ao enviar anexo', ['error' => $e->getMessage()]);
            $this->flashErro = 'Não foi possível enviar o documento. Tente de novo em instantes.';
        }
    }

    // ─── Criação ────────────────────────────────────────────────
    public function salvarNova(): void
    {
        $this->validarFormulario();

        try {
            $frequencia = RecorrenciaTransacao::from($this->recorrencia);

            if ($frequencia->repete()) {
                app(\App\Actions\CriarLancamentoRecorrenteAction::class)
                    ->execute($this->buildData(), $this->recorrenciaAte ?: null, $this->recorrenciaPago);
            } else {
                app(CreateTransacaoAction::class)->execute($this->buildData());
            }

            $this->flashSucesso = $frequencia->repete()
                ? 'Lançamento salvo. Ele vai se repetir ' . mb_strtolower($frequencia->label()) . ' (veja em Recorrências).'
                : 'Lançamento salvo.';
            $this->modalCriar   = false;
            $this->resetFormulario();
        } catch (Throwable $e) {
            Log::error('Erro ao criar transação', ['error' => $e->getMessage()]);
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar o lançamento');
        }
    }

    // ─── Atualização ────────────────────────────────────────────
    public function salvarEdicao(): void
    {
        $this->validarFormulario();

        try {
            $transacao = Transacao::findOrFail($this->transacaoEditandoId);
            $action    = app(UpdateTransacaoAction::class);
            $action->execute($transacao, $this->buildData());

            $this->flashSucesso        = 'Alterações salvas.';
            $this->modalEditar         = false;
            $this->transacaoEditandoId = null;
            $this->resetFormulario();
        } catch (Throwable $e) {
            Log::error('Erro ao atualizar transação', ['error' => $e->getMessage()]);
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar as alterações');
        }
    }

    // ─── Cancelar transação ─────────────────────────────────────
    public function cancelarTransacao(string $id): void
    {
        try {
            $transacao = Transacao::findOrFail($id);
            $action    = app(UpdateTransacaoAction::class);
            $action->execute($transacao, ['status' => StatusTransacao::Cancelado->value]);

            $this->flashSucesso = 'Lançamento cancelado.';
        } catch (Throwable $e) {
            Log::error('Erro ao cancelar transação', ['error' => $e->getMessage()]);
            $this->flashErro = 'Não foi possível cancelar o lançamento. Tente de novo em instantes.';
        }
    }

    // ─── Marcar como pago ───────────────────────────────────────
    public function marcarComoPago(string $id): void
    {
        try {
            $transacao = Transacao::findOrFail($id);
            if (! $transacao->status->emAberto()) {
                $this->flashErro = 'Só lançamentos em aberto podem ser marcados como pagos.';
                return;
            }
            app(UpdateTransacaoAction::class)->execute($transacao, [
                'status'         => StatusTransacao::Pago->value,
                'data_pagamento' => $transacao->data_pagamento?->toDateString() ?? now()->toDateString(),
            ]);
            $this->flashSucesso = 'Lançamento marcado como pago.';
        } catch (Throwable $e) {
            Log::error('Erro ao marcar transação como paga', ['id' => $id, 'error' => $e->getMessage()]);
            $this->flashErro = 'Não foi possível marcar como pago. Tente de novo em instantes.';
        }
    }

    // ─── Upload de anexos ────────────────────────────────────────
    public function uploadBoleto(): void
    {
        $this->validate(['arquivoBoleto' => 'required|file|max:102400|mimes:pdf,jpg,jpeg,png,docx']);

        try {
            $transacao = Transacao::findOrFail($this->transacaoDetalheId);
            $action    = app(UploadAnexoAction::class);
            $action->execute($transacao, $this->arquivoBoleto, TipoAnexo::Boleto);

            $this->arquivoBoleto = null;
            $this->flashSucesso  = 'Arquivo anexado.';
        } catch (Throwable $e) {
            Log::error('Erro ao fazer upload de boleto', ['error' => $e->getMessage()]);
            $this->flashErro = 'Não foi possível anexar o arquivo. Tente de novo em instantes.';
        }
    }

    public function uploadComprovante(): void
    {
        $this->validate(['arquivoComprovante' => 'required|file|max:102400|mimes:pdf,jpg,jpeg,png,docx']);

        try {
            $transacao = Transacao::findOrFail($this->transacaoDetalheId);
            $action    = app(UploadAnexoAction::class);
            $result    = $action->execute($transacao, $this->arquivoComprovante, TipoAnexo::Comprovante);

            $this->arquivoComprovante = null;

            if ($result['suggest_mark_paid']) {
                $this->flashSucesso = 'Comprovante anexado. Quer marcar o lançamento como pago?';
            } else {
                $this->flashSucesso = 'Comprovante anexado.';
            }
        } catch (Throwable $e) {
            Log::error('Erro ao fazer upload de comprovante', ['error' => $e->getMessage()]);
            $this->flashErro = 'Não foi possível anexar o comprovante. Tente de novo em instantes.';
        }
    }

    public function removerAnexo(string $anexoId): void
    {
        try {
            $anexo = TransacaoAnexo::where('transacao_id', $this->transacaoDetalheId)->findOrFail($anexoId);
            Storage::disk('local')->delete($anexo->caminho);
            $anexo->delete();
            $this->flashSucesso = 'Anexo excluído.';
        } catch (Throwable $e) {
            Log::error('Erro ao remover anexo', ['error' => $e->getMessage()]);
            $this->flashErro = 'Não foi possível excluir o anexo. Tente de novo em instantes.';
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────
    private function resetFormulario(): void
    {
        $this->tipo            = 'entrada';
        $this->fase            = 'operacao';
        $this->categoria       = '';
        $this->descricao       = '';
        $this->pacienteId      = '';
        $this->pacienteBusca   = '';
        $this->valorBruto      = '';
        $this->formaPagamento  = 'pix';
        $this->dataCompetencia = '';
        $this->dataPagamento   = '';
        $this->dataVencimento  = '';
        $this->contaFinanceiraId = '';
        $this->recebimentoCartao = 'padrao';
        $this->status          = 'pendente';
        $this->recorrencia     = 'unica';
        $this->recorrenciaAte  = '';
        $this->recorrenciaPago = false;
        $this->editandoRecorrente = false;
        $this->observacoes     = '';
        $this->taxaOperacional = null;
        $this->impostoEstimado = null;
        $this->valorLiquido    = null;
        $this->resetErrorBag();
    }

    private function validarFormulario(): void
    {
        $this->validate([
            'tipo'             => ['required', Rule::enum(TipoTransacao::class)],
            'fase'             => ['required', Rule::enum(FaseTransacao::class)],
            'categoria'        => 'required|string|max:100',
            'descricao'        => 'required|string|max:255',
            'valorBruto'       => 'required|numeric|min:0.01|max:99999999.99',
            'formaPagamento'   => ['required', Rule::enum(FormaPagamento::class)],
            'dataCompetencia'  => 'required|date',
            'dataPagamento'    => 'nullable|date|required_if:status,pago',
            'dataVencimento'   => 'nullable|date',
            'contaFinanceiraId' => ['nullable', 'uuid', Rule::exists('contas_financeiras', 'id')->where('tenant_id', app(\App\Support\ClinicaAtual::class)->id())],
            'status'           => ['required', Rule::enum(StatusTransacao::class)],
            'recorrencia'      => ['required', Rule::enum(RecorrenciaTransacao::class)],
            'recorrenciaAte'   => 'nullable|date|after_or_equal:dataCompetencia',
            'recorrenciaPago'  => 'boolean',
            'pacienteId'       => 'nullable|uuid|exists:pacientes,id',
            'observacoes'      => 'nullable|string|max:1000',
        ], [
            'dataPagamento.required_if' => 'Informe a data de pagamento, já que o lançamento está pago.',
            'recorrenciaAte.after_or_equal' => 'A repetição precisa terminar depois do primeiro lançamento.',
        ]);
    }

    private function buildData(): array
    {
        return [
            'tipo'             => $this->tipo,
            'fase'             => $this->fase,
            'categoria'        => $this->categoria,
            'descricao'        => $this->descricao,
            'paciente_id'      => $this->pacienteId ?: null,
            'valor_bruto'      => (float) str_replace(',', '.', $this->valorBruto),
            'forma_pagamento'  => $this->formaPagamento,
            'num_parcelas'     => FormaPagamento::from($this->formaPagamento)->parcelas(),
            'data_competencia' => $this->dataCompetencia,
            'data_pagamento'   => $this->dataPagamento ?: null,
            'data_vencimento'  => $this->dataVencimento ?: $this->dataCompetencia,
            'conta_financeira_id' => $this->contaFinanceiraId ?: null,
            'antecipar_cartao' => $this->recebimentoCartao === 'padrao' ? null : $this->recebimentoCartao === 'antecipado',
            'status'           => $this->status,
            'recorrencia'      => $this->recorrencia,
            'observacoes'      => $this->observacoes ?: null,
        ];
    }

    // ─── Paciente typeahead ──────────────────────────────────────
    #[Computed]
    public function pacientesFiltrados(): Collection
    {
        if (mb_strlen($this->pacienteBusca) < 2) {
            return collect();
        }

        return Paciente::where(function ($q): void {
            $q->where('nome', 'ilike', "%{$this->pacienteBusca}%")
                ->orWhere('cpf', 'like', "%{$this->pacienteBusca}%");
        })
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->limit(10)
            ->get(['id', 'nome', 'cpf']);
    }

    public function selecionarPaciente(string $id, string $nome): void
    {
        $this->pacienteId    = $id;
        $this->pacienteBusca = $nome;
    }

    // ─── Preenchimento por Voz ───────────────────────────────────
    public function preencherVoz(array $dados): void
    {
        $this->tipo            = $dados['tipo']            ?? $this->tipo;
        $this->fase            = $dados['fase']            ?? $this->fase;
        $this->categoria       = $dados['categoria']       ?? '';
        $this->descricao       = $dados['descricao']       ?? '';
        $this->valorBruto      = isset($dados['valor_bruto']) ? (string) $dados['valor_bruto'] : '';
        $this->formaPagamento  = $dados['forma_pagamento'] ?? 'pix';
        $this->dataCompetencia = $dados['data_competencia'] ?? '';
        $this->dataPagamento   = $dados['data_pagamento']  ?? '';
        $this->status          = $dados['status']          ?? 'pendente';
        $this->observacoes     = $dados['observacoes']     ?? '';
        $this->updatedStatus();
        $this->recalcularValores();
    }

    // ─── Transação de detalhe (computed) ─────────────────────────
    #[Computed]
    public function transacaoDetalhe(): ?Transacao
    {
        if ($this->transacaoDetalheId === null) {
            return null;
        }

        return Transacao::with([
            'anexos',
            'paciente:id,nome,email,telefone',
            'notasFiscais' => fn ($q) => $q->select(['id', 'transacao_id', 'status', 'numero', 'url', 'homologacao', 'created_at'])->latest()->limit(5),
        ])->find($this->transacaoDetalheId);
    }

    // ─── Render ──────────────────────────────────────────────────
    public function render(): View
    {
        $query = Transacao::query()
            ->select([
                'id', 'tipo', 'fase', 'categoria', 'descricao', 'cliente',
                'paciente_id', 'fornecedor_id',
                'valor_bruto', 'taxa_operacional', 'imposto_estimado', 'valor_liquido',
                'data_competencia', 'data_pagamento', 'forma_pagamento',
                'num_parcelas', 'status', 'recorrencia', 'recorrencia_id', 'observacoes',
            ])
            ->with(['paciente:id,nome'])
            ->orderBy('data_competencia', 'desc')
            ->orderBy('created_at', 'desc');

        if ($this->filtroTipo !== '') {
            $query->where('tipo', $this->filtroTipo);
        }

        if ($this->filtroFase !== '') {
            $query->where('fase', $this->filtroFase);
        }

        if ($this->filtroStatus !== '') {
            $query->where('status', $this->filtroStatus);
        }

        if ($this->filtroCategoria !== '') {
            $query->where('categoria', $this->filtroCategoria);
        }

        if ($this->periodoInicio !== '') {
            $query->where('data_competencia', '>=', $this->periodoInicio);
        }

        if ($this->periodoFim !== '') {
            $query->where('data_competencia', '<=', $this->periodoFim);
        }

        /** @var LengthAwarePaginator $transacoes */
        $transacoes = $query->paginate(20);

        // ─── Stats (mesmos filtros; sem filtro de status, ignora cancelados) ──
        $statsQuery = Transacao::query();

        if ($this->filtroStatus !== '') {
            $statsQuery->where('status', $this->filtroStatus);
        } else {
            $statsQuery->where('status', '!=', StatusTransacao::Cancelado->value);
        }
        if ($this->filtroTipo !== '') {
            $statsQuery->where('tipo', $this->filtroTipo);
        }
        if ($this->filtroFase !== '') {
            $statsQuery->where('fase', $this->filtroFase);
        }
        if ($this->filtroCategoria !== '') {
            $statsQuery->where('categoria', $this->filtroCategoria);
        }
        if ($this->periodoInicio !== '') {
            $statsQuery->where('data_competencia', '>=', $this->periodoInicio);
        }
        if ($this->periodoFim !== '') {
            $statsQuery->where('data_competencia', '<=', $this->periodoFim);
        }

        $stats = $statsQuery->selectRaw(
            "COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor_bruto ELSE 0 END), 0) AS total_entradas,
             COALESCE(SUM(CASE WHEN tipo = 'saida'   THEN valor_bruto ELSE 0 END), 0) AS total_saidas"
        )->first();

        $totalEntradas = (float) ($stats->total_entradas ?? 0);
        $totalSaidas   = (float) ($stats->total_saidas   ?? 0);
        $saldo         = $totalEntradas - $totalSaidas;

        return view('livewire.transacao-index', [
            'transacoes'        => $transacoes,
            'tiposEnum'         => TipoTransacao::cases(),
            'fasesEnum'         => FaseTransacao::cases(),
            'statusEnum'        => StatusTransacao::cases(),
            'contasFinanceiras' => \App\Models\ContaFinanceira::query()->ativas()->select(['id', 'nome'])->orderByDesc('padrao')->orderBy('nome')->limit(50)->get(),
            'formasPagamento'   => FormaPagamento::cases(),
            'recorrenciasEnum'  => RecorrenciaTransacao::cases(),
            'todasCategorias'   => array_values(array_unique([...\App\Models\PlanoConta::nomes('entrada'), ...\App\Models\PlanoConta::nomes('saida')])),
            'totalEntradas'     => $totalEntradas,
            'totalSaidas'       => $totalSaidas,
            'saldo'             => $saldo,
        ])->layout('layouts.app', ['title' => 'Lançamentos']);
    }
}
