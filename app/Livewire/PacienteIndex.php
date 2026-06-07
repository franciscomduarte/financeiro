<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreatePacienteAction;
use App\Actions\UpdatePacienteAction;
use App\Actions\UploadFotoPacienteAction;
use App\Models\Paciente;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class PacienteIndex extends Component
{
    use WithFileUploads, WithPagination;

    // ─── Filtros ────────────────────────────────────────────────
    #[Url(as: 'q', except: '')]
    public string $busca = '';

    #[Url(as: 'status', except: '')]
    public string $filtroStatus = '';

    // ─── Estado dos modais ──────────────────────────────────────
    public bool $modalCriar   = false;
    public bool $modalEditar  = false;
    public bool $modalDetalhe = false;

    public ?string $pacienteEditandoId = null;
    public ?string $pacienteDetalheId  = null;

    // ─── Campos do formulário ───────────────────────────────────
    public string $nome             = '';
    public string $cpf              = '';
    public string $dataNascimento   = '';
    public string $telefone         = '';
    public string $email            = '';
    public string $valorMensalidade = '';
    public string $formaPagamento   = 'pix';
    public string $anamnese         = '';
    public string $observacoes      = '';
    public string $status           = 'ativo';

    /** @var mixed */
    public $foto = null;

    // ─── Flash ─────────────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    // ─── Paginação reset ao filtrar ─────────────────────────────
    public function updatingBusca(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroStatus(): void
    {
        $this->resetPage();
    }

    // ─── Modal Criar ────────────────────────────────────────────
    public function abrirModalCriar(): void
    {
        $this->resetFormulario();
        $this->modalCriar = true;
    }

    public function salvar(CreatePacienteAction $action, UploadFotoPacienteAction $uploadAction): void
    {
        $this->validate($this->rules());

        try {
            $paciente = $action->execute($this->dadosFormulario());

            if ($this->foto !== null) {
                $uploadAction->execute($paciente, $this->foto);
            }

            $this->modalCriar    = false;
            $this->flashSucesso  = 'Paciente cadastrado com sucesso!';
            $this->resetFormulario();
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao cadastrar paciente: ' . $e->getMessage();
        }
    }

    // ─── Modal Editar ───────────────────────────────────────────
    public function abrirModalEditar(string $id): void
    {
        $paciente = Paciente::findOrFail($id);

        $this->pacienteEditandoId = $id;
        $this->nome               = $paciente->nome;
        $this->cpf                = $paciente->cpf ?? '';
        $this->dataNascimento     = $paciente->data_nascimento?->toDateString() ?? '';
        $this->telefone           = $paciente->telefone ?? '';
        $this->email              = $paciente->email ?? '';
        $this->valorMensalidade   = $paciente->valor_mensalidade ? (string) $paciente->valor_mensalidade : '';
        $this->formaPagamento     = $paciente->forma_pagamento ?? 'pix';
        $this->anamnese           = $paciente->anamnese ?? '';
        $this->observacoes        = $paciente->observacoes ?? '';
        $this->status             = $paciente->status->value;
        $this->foto               = null;

        $this->modalDetalhe = false;
        $this->modalEditar  = true;
    }

    public function atualizar(UpdatePacienteAction $action, UploadFotoPacienteAction $uploadAction): void
    {
        $this->validate($this->rules());

        try {
            $paciente = Paciente::findOrFail($this->pacienteEditandoId);
            $action->execute($paciente, $this->dadosFormulario());

            if ($this->foto !== null) {
                $uploadAction->execute($paciente->fresh(), $this->foto);
            }

            $this->modalEditar   = false;
            $this->flashSucesso  = 'Paciente atualizado com sucesso!';
            $this->resetFormulario();
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao atualizar paciente: ' . $e->getMessage();
        }
    }

    // ─── Modal Detalhe ──────────────────────────────────────────
    public function abrirDetalhe(string $id): void
    {
        $this->pacienteDetalheId = $id;
        $this->modalDetalhe      = true;
    }

    #[Computed]
    public function pacienteDetalhe(): ?Paciente
    {
        if ($this->pacienteDetalheId === null) {
            return null;
        }

        return Paciente::with([
            'transacoes' => fn ($q) => $q
                ->select(['id', 'tipo', 'descricao', 'valor_liquido', 'data_competencia', 'status', 'paciente_id'])
                ->orderBy('data_competencia', 'desc')
                ->limit(20),
        ])->find($this->pacienteDetalheId);
    }

    // ─── Fechar modais ──────────────────────────────────────────
    public function fecharModais(): void
    {
        $this->modalCriar         = false;
        $this->modalEditar        = false;
        $this->modalDetalhe       = false;
        $this->pacienteEditandoId = null;
        $this->pacienteDetalheId  = null;
        $this->resetFormulario();
        unset($this->pacienteDetalhe);
    }

    // ─── Render ─────────────────────────────────────────────────
    public function render(): View
    {
        $query = Paciente::query()
            ->select(['id', 'nome', 'cpf', 'telefone', 'email', 'status', 'foto_path', 'valor_mensalidade', 'forma_pagamento', 'created_at'])
            ->orderBy('nome');

        if ($this->filtroStatus !== '') {
            $query->where('status', $this->filtroStatus);
        }

        if ($this->busca !== '') {
            $term = $this->busca;
            $query->where(function ($q) use ($term): void {
                $q->where('nome', 'ilike', "%{$term}%")
                    ->orWhere('cpf', 'like', "%{$term}%")
                    ->orWhere('telefone', 'like', "%{$term}%")
                    ->orWhere('email', 'ilike', "%{$term}%");
            });
        }

        $pacientes   = $query->paginate(20);
        $totalCount  = Paciente::count();
        $ativosCount = Paciente::where('status', 'ativo')->count();

        return view('livewire.paciente-index', [
            'pacientes'   => $pacientes,
            'totalCount'  => $totalCount,
            'ativosCount' => $ativosCount,
        ])->layout('layouts.app', ['title' => 'Pacientes']);
    }

    // ─── Helpers privados ───────────────────────────────────────
    private function rules(): array
    {
        return [
            'nome'             => ['required', 'string', 'max:150'],
            'cpf'              => [
                'nullable', 'string', 'max:14',
                Rule::unique('pacientes', 'cpf')->ignore($this->pacienteEditandoId),
            ],
            'dataNascimento'   => ['nullable', 'date', 'before:today'],
            'telefone'         => ['nullable', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:150'],
            'valorMensalidade' => ['nullable', 'numeric', 'min:0'],
            'formaPagamento'   => ['required', 'in:pix,cartao,dinheiro,boleto'],
            'anamnese'         => ['nullable', 'string'],
            'observacoes'      => ['nullable', 'string'],
            'status'           => ['required', 'in:ativo,inativo'],
            'foto'             => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ];
    }

    private function dadosFormulario(): array
    {
        return [
            'nome'              => $this->nome,
            'cpf'               => $this->cpf ?: null,
            'data_nascimento'   => $this->dataNascimento ?: null,
            'telefone'          => $this->telefone ?: null,
            'email'             => $this->email ?: null,
            'valor_mensalidade' => $this->valorMensalidade !== '' ? (float) $this->valorMensalidade : 0,
            'forma_pagamento'   => $this->formaPagamento,
            'anamnese'          => $this->anamnese ?: null,
            'observacoes'       => $this->observacoes ?: null,
            'status'            => $this->status,
        ];
    }

    private function resetFormulario(): void
    {
        $this->nome               = '';
        $this->cpf                = '';
        $this->dataNascimento     = '';
        $this->telefone           = '';
        $this->email              = '';
        $this->valorMensalidade   = '';
        $this->formaPagamento     = 'pix';
        $this->anamnese           = '';
        $this->observacoes        = '';
        $this->status             = 'ativo';
        $this->foto               = null;
        $this->pacienteEditandoId = null;
        $this->resetValidation();
    }
}
