<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Atendimento\FinalizarAtendimentoAction;
use App\Actions\Atendimento\InjetaveisAtendimentoAction;
use App\Actions\Atendimento\PlanoTratamentoAction;
use App\Actions\Atendimento\SalvarRespostaAtendimentoAction;
use App\Actions\Prontuario\AdicionarFotosProntuarioAction;
use App\Enums\AcaoAcessoPaciente;
use App\Enums\MomentoFoto;
use App\Enums\Modulo;
use App\Enums\RoleUsuario;
use App\Enums\VisibilidadeAtendimento;
use App\Models\Agendamento;
use App\Models\Atendimento;
use App\Models\AtendimentoFicha;
use App\Models\FichaModelo;
use App\Models\Orcamento;
use App\Models\Paciente;
use App\Models\PlanoTratamento;
use App\Models\Procedimento;
use App\Models\ProntuarioFoto;
use App\Models\StockBatch;
use App\Models\StockProduct;
use App\Services\RegistroAcessoPaciente;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/**
 * Tela do atendimento: fichas (anamnese, capilar, facial...), fotos, injetáveis, orçamento e plano,
 * com salvamento automático, cronômetro, privacidade e finalização.
 */
class AtendimentoTela extends Component
{
    use Concerns\MensagemDeErro;
    use WithFileUploads;

    #[Locked]
    public string $atendimentoId;

    #[Url(except: '')]
    public string $secao = '';

    /** @var array<string, array<string, mixed>> modeloId => campoId => valor */
    public array $respostas = [];

    // ─── Fotos ──────────────────────────────────────────────────
    /** @var array<int, mixed> */
    public array $fotos = [];
    public string $fotoMomento = 'antes';
    public string $fotoRegiao = '';

    // ─── Injetáveis ─────────────────────────────────────────────
    public string $injProduto = '';
    public string $injLote = '';
    public string $injQuantidade = '';
    public string $injRegiao = '';
    public string $injObservacao = '';

    // ─── Plano ──────────────────────────────────────────────────
    /** @var array<int, array{procedimento_id: string, descricao: string, sessoes: string, intervalo_dias: string, valor_unitario: string}> */
    public array $planoItens = [];
    public string $planoObservacoes = '';

    public bool $modalFinalizar = false;

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(string $id, RegistroAcessoPaciente $registro): void
    {
        $atendimento = Atendimento::query()->select(['id', 'paciente_id'])->findOrFail($id);
        $this->atendimentoId = $atendimento->id;

        foreach (AtendimentoFicha::query()->where('atendimento_id', $atendimento->id)->get(['modelo_id', 'respostas']) as $f) {
            if ($f->modelo_id !== null) {
                $this->respostas[$f->modelo_id] = $f->respostas ?? [];
            }
        }

        $plano = PlanoTratamento::query()->with('itens')->where('atendimento_id', $atendimento->id)->first();
        $this->planoObservacoes = (string) $plano?->observacoes;
        $this->planoItens       = $plano?->itens->map(fn ($i) => [
            'procedimento_id' => (string) $i->procedimento_id, 'descricao' => $i->descricao, 'sessoes' => (string) $i->sessoes,
            'intervalo_dias'  => (string) $i->intervalo_dias, 'valor_unitario' => number_format((float) $i->valor_unitario, 2, '.', ''),
        ])->all() ?? [];

        $registro->registrar(Paciente::query()->select(['id', 'tenant_id'])->findOrFail($atendimento->paciente_id), AcaoAcessoPaciente::AbriuProntuario);
    }

    #[Computed]
    public function atendimento(): Atendimento
    {
        return Atendimento::query()
            ->with(['paciente:id,nome,data_nascimento,anamnese', 'profissional:id,nome', 'agendamento:id,inicio_em,procedimento_id,status', 'agendamento.procedimento:id,nome'])
            ->findOrFail($this->atendimentoId);
    }

    /** Quem pode mexer: quem abriu (ou admin), enquanto está em andamento. */
    #[Computed]
    public function editavel(): bool
    {
        $a    = $this->atendimento;
        $user = auth()->user();

        return $a->emAndamento() && ($user->role === RoleUsuario::Admin || $a->user_id === $user->id);
    }

    /** Fichas ativas (em andamento) ou as preenchidas (finalizado), com os campos a exibir. */
    #[Computed]
    public function fichas(): Collection
    {
        $salvas = AtendimentoFicha::query()->where('atendimento_id', $this->atendimentoId)->get(['id', 'modelo_id', 'titulo', 'campos', 'respostas'])->keyBy('modelo_id');

        if (! $this->atendimento->emAndamento()) {
            return $salvas->filter(fn (AtendimentoFicha $f) => $f->preenchida())
                ->map(fn (AtendimentoFicha $f) => ['id' => (string) $f->modelo_id, 'nome' => $f->titulo, 'campos' => $f->campos])->values();
        }

        return FichaModelo::query()->select(['id', 'nome', 'campos', 'ordem'])->where('ativo', true)->orderBy('ordem')->orderBy('nome')->get()
            ->map(fn (FichaModelo $m) => ['id' => $m->id, 'nome' => $m->nome, 'campos' => $salvas->get($m->id)?->campos ?? $m->campos]);
    }

    /** @return array<string, string> chave => rótulo do menu lateral */
    #[Computed]
    public function secoes(): array
    {
        $secoes = [];
        foreach ($this->fichas as $f) {
            $secoes['ficha:' . $f['id']] = $f['nome'];
        }

        return $secoes + ['fotos' => 'Fotos', 'injetaveis' => 'Injetáveis', 'orcamento' => 'Orçamento', 'plano' => 'Plano de tratamento'];
    }

    public function secaoAtual(): string
    {
        return array_key_exists($this->secao, $this->secoes) ? $this->secao : (string) array_key_first($this->secoes);
    }

    public function irPara(string $secao): void
    {
        $this->secao = array_key_exists($secao, $this->secoes) ? $secao : '';
        $this->flashErro = $this->flashSucesso = null;
    }

    // ─── Respostas (salvamento automático) ──────────────────────

    public function updatedRespostas(mixed $valor, string $chave): void
    {
        [$modeloId, $campoId] = array_pad(explode('.', $chave, 3), 2, '');
        $this->salvarResposta($modeloId, $campoId, $valor);
    }

    /** Usado pelo editor de texto (que não usa wire:model para não perder o cursor). */
    public function salvarTextoRico(string $modeloId, string $campoId, string $html): void
    {
        $this->salvarResposta($modeloId, $campoId, $html, false);
    }

    private function salvarResposta(string $modeloId, string $campoId, mixed $valor, bool $devolver = true): void
    {
        try {
            $limpo = app(SalvarRespostaAtendimentoAction::class)->execute($this->atendimentoId, $modeloId, $campoId, $valor);
            if ($devolver) {
                $this->respostas[$modeloId][$campoId] = $limpo;
            }
            $this->flashErro = null;
            unset($this->fichas);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar a resposta');
        }
    }

    public function alterarVisibilidade(string $valor, FinalizarAtendimentoAction $acao): void
    {
        try {
            $acao->alterarVisibilidade($this->atendimentoId, VisibilidadeAtendimento::from($valor));
            unset($this->atendimento);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível alterar');
        }
    }

    // ─── Fotos ──────────────────────────────────────────────────

    #[Computed]
    public function fotosDoAtendimento(): Collection
    {
        return ProntuarioFoto::query()->select(['id', 'momento', 'regiao', 'miniatura_path', 'tirada_em'])
            ->where('atendimento_id', $this->atendimentoId)->latest()->limit(60)->get();
    }

    public function adicionarFotos(AdicionarFotosProntuarioAction $adicionar): void
    {
        $this->validate([
            'fotos'       => ['required', 'array', 'min:1', 'max:10'],
            'fotos.*'     => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'fotoMomento' => ['required', \Illuminate\Validation\Rule::enum(MomentoFoto::class)],
            'fotoRegiao'  => ['nullable', 'string', 'max:80'],
        ], [
            'fotos.required' => 'Escolha pelo menos uma foto.',
            'fotos.*.image'  => 'Envie só imagens (JPG, PNG ou WEBP).',
            'fotos.*.mimes'  => 'Envie só imagens (JPG, PNG ou WEBP).',
            'fotos.*.max'    => 'Cada foto pode ter até 10 MB.',
        ]);

        try {
            $a = $this->atendimento;
            $adicionar->execute(
                Paciente::query()->findOrFail($a->paciente_id), $this->fotos, MomentoFoto::from($this->fotoMomento),
                now(config('clinica.fuso_horario'))->toDateString(), $this->fotoRegiao, null, $a->agendamento_id, $a->id,
            );
            $this->reset('fotos', 'fotoRegiao');
            unset($this->fotosDoAtendimento);
            $this->flashSucesso = 'Fotos salvas no prontuário.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar as fotos');
        }
    }

    // ─── Injetáveis ─────────────────────────────────────────────

    #[Computed]
    public function produtos(): Collection
    {
        return StockProduct::query()->active()->select(['id', 'name', 'unit_type'])->orderBy('name')->limit(300)->get();
    }

    #[Computed]
    public function lotes(): Collection
    {
        return $this->injProduto === '' ? collect() : StockBatch::query()->available()->fefo()
            ->select(['id', 'lot_number', 'expires_at', 'quantity_available', 'status'])
            ->where('product_id', (int) $this->injProduto)->limit(30)->get();
    }

    #[Computed]
    public function injetaveis(): Collection
    {
        return $this->atendimento->injetaveis()->with(['produto:id,name,unit_type', 'lote:id,lot_number'])->oldest()->get();
    }

    public function updatedInjProduto(): void
    {
        $this->injLote = '';
    }

    public function adicionarInjetavel(InjetaveisAtendimentoAction $acao): void
    {
        $this->validate([
            'injProduto'    => ['required'],
            'injQuantidade' => ['required', 'regex:/^\d+([.,]\d{1,3})?$/'],
            'injRegiao'     => ['nullable', 'string', 'max:120'],
            'injObservacao' => ['nullable', 'string', 'max:255'],
        ], [
            'injProduto.required'    => 'Escolha o produto.',
            'injQuantidade.required' => 'Informe a quantidade.',
            'injQuantidade.regex'    => 'Use só números. Ex.: 20 ou 0,5',
        ]);

        try {
            $acao->adicionar($this->atendimentoId, [
                'product_id' => $this->injProduto, 'batch_id' => $this->injLote ?: null, 'quantidade' => $this->injQuantidade,
                'regiao'     => $this->injRegiao, 'observacao' => $this->injObservacao,
            ]);
            $this->reset('injQuantidade', 'injRegiao', 'injObservacao');
            unset($this->injetaveis);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível registrar');
        }
    }

    public function removerInjetavel(string $itemId, InjetaveisAtendimentoAction $acao): void
    {
        try {
            $acao->remover($this->atendimentoId, $itemId);
            unset($this->injetaveis);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível remover');
        }
    }

    // ─── Orçamento e plano ──────────────────────────────────────

    #[Computed]
    public function orcamentos(): Collection
    {
        return Orcamento::query()->select(['id', 'numero', 'status', 'total', 'created_at'])
            ->where('paciente_id', $this->atendimento->paciente_id)->latest()->limit(5)->get();
    }

    #[Computed]
    public function procedimentos(): Collection
    {
        return Procedimento::query()->select(['id', 'nome', 'valor', 'retorno_dias'])->where('ativo', true)->orderBy('nome')->limit(300)->get();
    }

    #[Computed]
    public function plano(): ?PlanoTratamento
    {
        return PlanoTratamento::query()->with(['itens', 'orcamento:id,numero,status'])->where('atendimento_id', $this->atendimentoId)->first();
    }

    public function adicionarItemPlano(): void
    {
        $this->planoItens[] = ['procedimento_id' => '', 'descricao' => '', 'sessoes' => '1', 'intervalo_dias' => '', 'valor_unitario' => ''];
    }

    public function removerItemPlano(int $i): void
    {
        unset($this->planoItens[$i]);
        $this->planoItens = array_values($this->planoItens);
    }

    public function updatedPlanoItens(mixed $valor, string $chave): void
    {
        [$i, $campo] = array_pad(explode('.', $chave), 2, '');
        if ($campo === 'procedimento_id' && isset($this->planoItens[(int) $i])) {
            $proc = $this->procedimentos->firstWhere('id', (int) $valor);
            if ($proc !== null) {
                $this->planoItens[(int) $i]['descricao']      = $proc->nome;
                $this->planoItens[(int) $i]['valor_unitario'] = number_format((float) $proc->valor, 2, '.', '');
                $this->planoItens[(int) $i]['intervalo_dias'] = $proc->retorno_dias ? (string) $proc->retorno_dias : $this->planoItens[(int) $i]['intervalo_dias'];
            }
        }
    }

    public function salvarPlano(PlanoTratamentoAction $acao): void
    {
        $this->validate([
            'planoItens'                  => ['array', 'max:30'],
            'planoItens.*.descricao'      => ['required', 'string', 'max:150'],
            'planoItens.*.sessoes'        => ['required', 'integer', 'min:1', 'max:100'],
            'planoItens.*.intervalo_dias' => ['nullable', 'integer', 'min:1', 'max:365'],
            'planoItens.*.valor_unitario' => ['nullable', 'regex:/^\d+([.,]\d{1,2})?$/'],
            'planoObservacoes'            => ['nullable', 'string', 'max:2000'],
        ], [
            'planoItens.*.descricao.required' => 'Escreva o procedimento.',
            'planoItens.*.sessoes.*'          => 'Sessões de 1 a 100.',
            'planoItens.*.intervalo_dias.*'   => 'Intervalo de 1 a 365 dias.',
            'planoItens.*.valor_unitario.*'   => 'Valor inválido.',
        ]);

        try {
            $acao->salvar($this->atendimentoId, $this->planoItens, $this->planoObservacoes);
            unset($this->plano);
            $this->flashSucesso = 'Plano de tratamento salvo.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar o plano');
        }
    }

    public function gerarOrcamento(PlanoTratamentoAction $acao): void
    {
        try {
            if ($this->editavel) {
                $acao->salvar($this->atendimentoId, $this->planoItens, $this->planoObservacoes);
            }
            $orcamento = $acao->gerarOrcamento($this->atendimentoId);
            unset($this->plano, $this->orcamentos);
            $this->flashSucesso = "Orçamento nº {$orcamento->numero} criado. Envie ou aprove em Orçamentos.";
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível gerar o orçamento');
        }
    }

    // ─── Finalizar / cancelar ───────────────────────────────────

    public function finalizar(FinalizarAtendimentoAction $acao): mixed
    {
        try {
            $a = $acao->finalizar($this->atendimentoId);
        } catch (Throwable $e) {
            $this->modalFinalizar = false;
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível finalizar');

            return null;
        }

        session()->flash('success', 'Atendimento finalizado e salvo no prontuário.');

        // Agendamento ainda aberto: segue para a conclusão na agenda (receita/pacote)
        $agendamento = $a->agendamento_id ? Agendamento::query()->select(['id', 'status'])->find($a->agendamento_id) : null;
        if ($agendamento?->status->isPendente() && auth()->user()->pode(Modulo::Agenda)) {
            return $this->redirectRoute('agenda.index', ['concluir' => $agendamento->id], navigate: true);
        }

        return $this->redirectRoute('pacientes.prontuario', ['id' => $a->paciente_id], navigate: true);
    }

    public function cancelar(FinalizarAtendimentoAction $acao): mixed
    {
        $pacienteId = $this->atendimento->paciente_id;
        try {
            $acao->cancelar($this->atendimentoId);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível cancelar');

            return null;
        }

        return $this->redirectRoute('pacientes.prontuario', ['id' => $pacienteId], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.atendimento-tela', ['atual' => $this->secaoAtual()]);
    }
}
