<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateDocumentoAction;
use App\Actions\CreateDocumentoCategoriaAction;
use App\Actions\RenovarDocumentoAction;
use App\Actions\UpdateDocumentoAction;
use App\Actions\UpdateDocumentoCategoriaAction;
use App\Enums\StatusDocumento;
use App\Models\Documento;
use App\Models\DocumentoCategoria;
use App\Models\DocumentoVersao;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class DocumentoIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    // ─── Filtros ────────────────────────────────────────────────
    public string $filtroCategoria = '';
    public string $filtroStatus    = '';
    public string $busca           = '';

    // ─── Modais ─────────────────────────────────────────────────
    public bool $modalCategoria  = false;
    public bool $modalDocumento  = false;
    public bool $modalRenovar    = false;
    public bool $modalHistorico  = false;

    // ─── IDs ativos ─────────────────────────────────────────────
    public ?string $categoriaEditandoId = null;
    public ?string $documentoEditandoId = null;
    public ?string $documentoRenovarId  = null;
    public ?string $documentoHistoricoId = null;

    // ─── Form: Categoria ────────────────────────────────────────
    public string $catNome            = '';
    public string $catDescricao       = '';
    public string $catCor             = 'slate';
    public bool   $catRequerValidade  = true;
    public string $catAlertaDias      = '30';

    // ─── Form: Documento ────────────────────────────────────────
    public string  $docCategoriaId     = '';
    public string  $docTitulo          = '';
    public string  $docNumero          = '';
    public string  $docOrgao           = '';
    public string  $docResponsavel     = '';
    public string  $docDataEmissao     = '';
    public string  $docDataValidade    = '';
    public string  $docAlertaDias      = '';
    public string  $docStatus          = 'vigente';
    public string  $docObservacoes     = '';
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public mixed   $docArquivo         = null;

    // ─── Form: Renovar ──────────────────────────────────────────
    public string $renNumero       = '';
    public string $renDataEmissao  = '';
    public string $renDataValidade = '';
    public string $renObservacoes  = '';
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public mixed  $renArquivo      = null;

    // ─── Flash ──────────────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    protected function queryString(): array
    {
        return [
            'filtroCategoria' => ['except' => '', 'as' => 'cat'],
            'filtroStatus'    => ['except' => '', 'as' => 'status'],
            'busca'           => ['except' => '', 'as' => 'q'],
        ];
    }

    public function updatingFiltroCategoria(): void { $this->resetPage(); }
    public function updatingFiltroStatus(): void    { $this->resetPage(); }
    public function updatingBusca(): void           { $this->resetPage(); }

    // ─── Modal Categoria ────────────────────────────────────────
    public function abrirModalCategoria(?string $id = null): void
    {
        $this->resetFormCategoria();
        if ($id) {
            $cat = DocumentoCategoria::findOrFail($id);
            $this->categoriaEditandoId = $id;
            $this->catNome           = $cat->nome;
            $this->catDescricao      = $cat->descricao ?? '';
            $this->catCor            = $cat->cor;
            $this->catRequerValidade = $cat->requer_validade;
            $this->catAlertaDias     = (string) $cat->alerta_dias_antes;
        }
        $this->modalCategoria = true;
    }

    public function salvarCategoria(CreateDocumentoCategoriaAction $criar, UpdateDocumentoCategoriaAction $atualizar): void
    {
        $this->validate($this->rulesCat());
        try {
            $dados = [
                'nome'             => $this->catNome,
                'descricao'        => $this->catDescricao ?: null,
                'cor'              => $this->catCor,
                'requer_validade'  => $this->catRequerValidade,
                'alerta_dias_antes' => (int) $this->catAlertaDias,
            ];
            if ($this->categoriaEditandoId) {
                $atualizar->execute(DocumentoCategoria::findOrFail($this->categoriaEditandoId), $dados);
                $this->flashSucesso = 'Categoria salva.';
            } else {
                $criar->execute($dados);
                $this->flashSucesso = 'Categoria criada.';
            }
            $this->modalCategoria = false;
            $this->resetFormCategoria();
        } catch (Throwable $e) {
            report($e);
            $this->flashErro = 'Não foi possível salvar a categoria. Tente de novo em instantes.';
        }
    }

    // ─── Modal Documento ────────────────────────────────────────
    public function abrirModalDocumento(?string $id = null): void
    {
        $this->resetFormDocumento();
        if ($id) {
            $doc = Documento::findOrFail($id);
            $this->documentoEditandoId = $id;
            $this->docCategoriaId  = $doc->categoria_id;
            $this->docTitulo       = $doc->titulo;
            $this->docNumero       = $doc->numero_documento ?? '';
            $this->docOrgao        = $doc->orgao_emissor ?? '';
            $this->docResponsavel  = $doc->responsavel ?? '';
            $this->docDataEmissao  = $doc->data_emissao?->format('Y-m-d') ?? '';
            $this->docDataValidade = $doc->data_validade?->format('Y-m-d') ?? '';
            $this->docAlertaDias   = $doc->alerta_dias_antes !== null ? (string) $doc->alerta_dias_antes : '';
            $this->docStatus       = $doc->status->value;
            $this->docObservacoes  = $doc->observacoes ?? '';
        }
        $this->modalDocumento = true;
    }

    public function salvarDocumento(CreateDocumentoAction $criar, UpdateDocumentoAction $atualizar): void
    {
        $this->validate($this->rulesDoc());
        try {
            $dados = [
                'categoria_id'     => $this->docCategoriaId,
                'titulo'           => $this->docTitulo,
                'numero_documento' => $this->docNumero ?: null,
                'orgao_emissor'    => $this->docOrgao ?: null,
                'responsavel'      => $this->docResponsavel ?: null,
                'data_emissao'     => $this->docDataEmissao ?: null,
                'data_validade'    => $this->docDataValidade ?: null,
                'alerta_dias_antes' => $this->docAlertaDias !== '' ? (int) $this->docAlertaDias : null,
                'status'           => $this->docStatus,
                'observacoes'      => $this->docObservacoes ?: null,
            ];
            $arquivo = $this->resolveArquivo($this->docArquivo);
            if ($this->documentoEditandoId) {
                $atualizar->execute(Documento::findOrFail($this->documentoEditandoId), $dados, $arquivo);
                $this->flashSucesso = 'Documento salvo.';
            } else {
                $criar->execute($dados, $arquivo);
                $this->flashSucesso = 'Documento cadastrado.';
            }
            $this->modalDocumento = false;
            $this->resetFormDocumento();
        } catch (Throwable $e) {
            report($e);
            $this->flashErro = 'Não foi possível salvar o documento. Tente de novo em instantes.';
        }
    }

    // ─── Modal Renovar ──────────────────────────────────────────
    public function abrirModalRenovar(string $id): void
    {
        $doc = Documento::findOrFail($id);
        $this->documentoRenovarId = $id;
        $this->renNumero       = $doc->numero_documento ?? '';
        $this->renDataEmissao  = now()->format('Y-m-d');
        $this->renDataValidade = '';
        $this->renObservacoes  = '';
        $this->renArquivo      = null;
        $this->modalRenovar    = true;
    }

    public function renovar(RenovarDocumentoAction $action): void
    {
        $this->validate([
            'renDataEmissao'  => ['required', 'date'],
            'renDataValidade' => ['nullable', 'date', 'after:renDataEmissao'],
            'renArquivo'      => ['nullable', 'file', 'max:102400'],
        ]);
        try {
            $arquivo = $this->resolveArquivo($this->renArquivo);
            $action->execute(Documento::findOrFail($this->documentoRenovarId), [
                'numero_documento' => $this->renNumero ?: null,
                'data_emissao'     => $this->renDataEmissao,
                'data_validade'    => $this->renDataValidade ?: null,
                'observacoes'      => $this->renObservacoes ?: null,
            ], $arquivo);
            $this->modalRenovar  = false;
            $this->flashSucesso  = 'Documento renovado.';
        } catch (Throwable $e) {
            report($e);
            $this->flashErro = 'Não foi possível renovar o documento. Tente de novo em instantes.';
        }
    }

    // ─── Modal Histórico ────────────────────────────────────────
    public function abrirModalHistorico(string $id): void
    {
        $this->documentoHistoricoId = $id;
        $this->modalHistorico       = true;
    }

    // ─── Fechar modais ──────────────────────────────────────────
    public function fecharModais(): void
    {
        $this->modalCategoria   = false;
        $this->modalDocumento   = false;
        $this->modalRenovar     = false;
        $this->modalHistorico   = false;
        $this->flashErro        = null;
        $this->resetFormCategoria();
        $this->resetFormDocumento();
    }

    // ─── Helpers privados ────────────────────────────────────────
    private function resetFormCategoria(): void
    {
        $this->categoriaEditandoId = null;
        $this->catNome             = '';
        $this->catDescricao        = '';
        $this->catCor              = 'slate';
        $this->catRequerValidade   = true;
        $this->catAlertaDias       = '30';
    }

    private function resolveArquivo(mixed $value): ?\Livewire\Features\SupportFileUploads\TemporaryUploadedFile
    {
        if ($value instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            return $value;
        }
        if (is_string($value) && \Livewire\Features\SupportFileUploads\TemporaryUploadedFile::canUnserialize($value)) {
            return \Livewire\Features\SupportFileUploads\TemporaryUploadedFile::unserializeFromLivewireRequest($value);
        }
        return null;
    }

    private function resetFormDocumento(): void
    {
        $this->documentoEditandoId = null;
        $this->docCategoriaId      = '';
        $this->docTitulo           = '';
        $this->docNumero           = '';
        $this->docOrgao            = '';
        $this->docResponsavel      = '';
        $this->docDataEmissao      = '';
        $this->docDataValidade     = '';
        $this->docAlertaDias       = '';
        $this->docStatus           = 'vigente';
        $this->docObservacoes      = '';
        $this->docArquivo          = null;
    }

    protected function messages(): array
    {
        return [
            'docArquivo.max' => 'O arquivo não pode ser maior que 100 MB.',
            'renArquivo.max' => 'O arquivo não pode ser maior que 100 MB.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'docCategoriaId'  => 'categoria',
            'docTitulo'       => 'título',
            'docNumero'       => 'número do documento',
            'docOrgao'        => 'órgão emissor',
            'docResponsavel'  => 'responsável',
            'docDataEmissao'  => 'data de emissão',
            'docDataValidade' => 'data de validade',
            'docAlertaDias'   => 'alerta em dias',
            'docStatus'       => 'status',
            'docArquivo'      => 'arquivo',
            'renArquivo'      => 'arquivo',
            'catNome'         => 'nome',
            'catDescricao'    => 'descrição',
            'catCor'          => 'cor',
            'catAlertaDias'   => 'alerta em dias',
        ];
    }

    private function rulesCat(): array
    {
        return [
            'catNome'           => ['required', 'string', 'max:100'],
            'catDescricao'      => ['nullable', 'string', 'max:255'],
            'catCor'            => ['required', 'in:red,orange,amber,green,blue,indigo,purple,slate'],
            'catAlertaDias'     => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    private function rulesDoc(): array
    {
        return [
            'docCategoriaId'  => ['required', 'exists:documento_categorias,id'],
            'docTitulo'       => ['required', 'string', 'max:200'],
            'docNumero'       => ['nullable', 'string', 'max:100'],
            'docOrgao'        => ['nullable', 'string', 'max:150'],
            'docResponsavel'  => ['nullable', 'string', 'max:150'],
            'docDataEmissao'  => ['nullable', 'date'],
            'docDataValidade' => ['nullable', 'date'],
            'docAlertaDias'   => ['nullable', 'integer', 'min:1', 'max:365'],
            'docStatus'       => ['required', 'in:vigente,renovando,arquivado'],
            'docArquivo'      => ['nullable', 'file', 'max:102400'],
        ];
    }

    public function render(): View
    {
        $categorias = DocumentoCategoria::orderBy('nome')->get();

        $documentos = Documento::query()
            ->with('categoria')
            ->when($this->filtroCategoria !== '', fn ($q) => $q->where('categoria_id', $this->filtroCategoria))
            ->when($this->filtroStatus !== '', function ($q): void {
                match ($this->filtroStatus) {
                    'vencido'  => $q->where('status', '!=', 'arquivado')
                                    ->whereNotNull('data_validade')
                                    ->where('data_validade', '<', now()->toDateString()),
                    'vencendo' => $q->where('status', '!=', 'arquivado')
                                    ->whereNotNull('data_validade')
                                    ->whereBetween('data_validade', [now()->toDateString(), now()->addDays(60)->toDateString()]),
                    default    => $q->where('status', $this->filtroStatus),
                };
            })
            ->when($this->busca !== '', fn ($q) => $q->where(function ($q2): void {
                $q2->where('titulo', 'ilike', '%' . $this->busca . '%')
                   ->orWhere('numero_documento', 'ilike', '%' . $this->busca . '%')
                   ->orWhere('orgao_emissor', 'ilike', '%' . $this->busca . '%')
                   ->orWhere('responsavel', 'ilike', '%' . $this->busca . '%');
            }))
            ->orderBy('titulo')
            ->paginate(20);

        $totalVigentes  = Documento::where('status', 'vigente')->count();
        $totalVencendo  = Documento::where('status', '!=', 'arquivado')
            ->whereNotNull('data_validade')
            ->whereBetween('data_validade', [now()->toDateString(), now()->addDays(60)->toDateString()])
            ->count();
        $totalVencidos  = Documento::where('status', '!=', 'arquivado')
            ->whereNotNull('data_validade')
            ->where('data_validade', '<', now()->toDateString())
            ->count();
        $totalDocumentos = Documento::count();

        $versoes = $this->documentoHistoricoId
            ? DocumentoVersao::where('documento_id', $this->documentoHistoricoId)
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $documentoHistorico = $this->documentoHistoricoId
            ? Documento::find($this->documentoHistoricoId)
            : null;

        return view('livewire.documento-index', [
            'categorias'        => $categorias,
            'documentos'        => $documentos,
            'totalDocumentos'   => $totalDocumentos,
            'totalVigentes'     => $totalVigentes,
            'totalVencendo'     => $totalVencendo,
            'totalVencidos'     => $totalVencidos,
            'versoes'           => $versoes,
            'documentoHistorico' => $documentoHistorico,
            'statusOpcoes'      => StatusDocumento::cases(),
        ])->layout('layouts.app', ['title' => 'Documentos']);
    }
}
