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
    use Concerns\MensagemDeErro;
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

    // ─── LGPD ───────────────────────────────────────────────────
    public bool $consentimento           = false;
    public bool $aceitaWhatsappMarketing = false;
    public bool $aceitaEmailMarketing    = false;

    /** @var mixed */
    public $foto = null;

    /** Perfil vê anamnese e notas clínicas (admin, profissional) */
    private function veDadosClinicos(): bool
    {
        return (bool) auth()->user()?->pode(\App\Enums\Modulo::DadosClinicos);
    }

    /** Perfil vê valores e cobranças do paciente (admin, recepção, financeiro) */
    private function veFinanceiro(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->pode(\App\Enums\Modulo::Cobrancas) || $user?->pode(\App\Enums\Modulo::Lancamentos));
    }

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
            $this->gravarConsentimento($paciente);

            if ($this->foto !== null) {
                $uploadAction->execute($paciente, $this->foto);
            }

            $this->modalCriar    = false;
            $this->flashSucesso  = 'Paciente salvo.';
            $this->resetFormulario();
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar o paciente');
        }
    }

    // ─── Modal Editar ───────────────────────────────────────────
    public function abrirModalEditar(string $id): void
    {
        $paciente = Paciente::findOrFail($id);
        abort_if($paciente->anonimizado(), 403, 'Paciente anonimizado não pode ser editado.');

        $this->pacienteEditandoId = $id;
        $this->nome               = $paciente->nome;
        $this->cpf                = $paciente->cpf ?? '';
        $this->dataNascimento     = $paciente->data_nascimento?->toDateString() ?? '';
        $this->telefone           = $paciente->telefone ?? '';
        $this->email              = $paciente->email ?? '';
        $this->valorMensalidade   = $paciente->valor_mensalidade ? (string) $paciente->valor_mensalidade : '';
        $this->formaPagamento     = $paciente->forma_pagamento ?? 'pix';
        $this->anamnese           = $this->veDadosClinicos() ? ($paciente->anamnese ?? '') : '';
        $this->observacoes        = $paciente->observacoes ?? '';
        $this->status             = $paciente->status->value;
        $this->foto               = null;
        $this->consentimento           = $paciente->consentimento_em !== null;
        $this->aceitaWhatsappMarketing = $paciente->aceita_whatsapp_marketing;
        $this->aceitaEmailMarketing    = $paciente->aceita_email_marketing;

        $this->modalDetalhe = false;
        $this->modalEditar  = true;
    }

    public function atualizar(UpdatePacienteAction $action, UploadFotoPacienteAction $uploadAction): void
    {
        $this->validate($this->rules());

        try {
            $paciente = Paciente::findOrFail($this->pacienteEditandoId);
            $action->execute($paciente, $this->dadosFormulario());
            $this->gravarConsentimento($paciente);
            app(\App\Services\RegistroAcessoPaciente::class)->registrar($paciente, \App\Enums\AcaoAcessoPaciente::Editou);

            if ($this->foto !== null) {
                $uploadAction->execute($paciente->fresh(), $this->foto);
            }

            $this->modalEditar   = false;
            $this->flashSucesso  = 'Alterações salvas.';
            $this->resetFormulario();
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar as alterações');
        }
    }

    // ─── Modal Detalhe ──────────────────────────────────────────
    public function abrirDetalhe(string $id): void
    {
        $paciente = Paciente::findOrFail($id);
        app(\App\Services\RegistroAcessoPaciente::class)->registrar($paciente, \App\Enums\AcaoAcessoPaciente::Visualizou);

        $this->pacienteDetalheId = $id;
        $this->modalDetalhe      = true;
    }

    /** LGPD: apaga dados pessoais e clínicos e mantém o financeiro (só administradores). */
    public function anonimizar(\App\Actions\AnonimizarPacienteAction $action): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        try {
            $action->execute(Paciente::findOrFail($this->pacienteDetalheId));
            unset($this->pacienteDetalhe);
            $this->flashSucesso = 'Dados pessoais apagados. Os lançamentos financeiros continuam guardados, sem identificar o paciente.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível anonimizar o paciente');
        }
    }

    /** Consentimento LGPD: guarda quando e quem registrou; desmarcar apaga o registro. */
    private function gravarConsentimento(Paciente $paciente): void
    {
        $paciente->forceFill([
            'consentimento_em'          => $this->consentimento ? ($paciente->consentimento_em ?? now()) : null,
            'consentimento_por'         => $this->consentimento ? ($paciente->consentimento_por ?? auth()->id()) : null,
            'aceita_whatsapp_marketing' => $this->aceitaWhatsappMarketing,
            'aceita_email_marketing'    => $this->aceitaEmailMarketing,
        ])->save();
    }

    #[Computed]
    public function pacienteDetalhe(): ?Paciente
    {
        if ($this->pacienteDetalheId === null) {
            return null;
        }

        $relacoes = $this->veFinanceiro() ? [
            'transacoes' => fn ($q) => $q
                ->select(['id', 'tipo', 'descricao', 'valor_liquido', 'data_competencia', 'status', 'paciente_id'])
                ->orderBy('data_competencia', 'desc')
                ->limit(20),
            'pacotes' => fn ($q) => $q
                ->select(['id', 'paciente_id', 'nome', 'sessoes_total', 'sessoes_usadas', 'validade', 'status'])
                ->where('status', \App\Enums\StatusPacote::Ativo)
                ->orderBy('created_at')
                ->limit(20),
        ] : [];

        return Paciente::with($relacoes)->find($this->pacienteDetalheId);
    }

    /** Últimos acessos aos dados do paciente aberto (só administradores). */
    #[Computed]
    public function acessosDetalhe(): array
    {
        if ($this->pacienteDetalheId === null || ! auth()->user()?->isAdmin()) {
            return [];
        }

        $paciente = Paciente::find($this->pacienteDetalheId);

        return $paciente ? app(\App\Actions\ConsultarAcessosPacienteAction::class)->execute($paciente, 15) : [];
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
            ->select(['id', 'nome', 'cpf', 'telefone', 'email', 'status', 'foto_path', 'valor_mensalidade', 'forma_pagamento', 'anonimizado_em', 'created_at'])
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
            'veDadosClinicos' => $this->veDadosClinicos(),
            'veFinanceiro'    => $this->veFinanceiro(),
            'ehAdmin'         => (bool) auth()->user()?->isAdmin(),
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
            'consentimento'           => ['boolean'],
            'aceitaWhatsappMarketing' => ['boolean'],
            'aceitaEmailMarketing'    => ['boolean'],
        ];
    }

    private function dadosFormulario(): array
    {
        $dados = [
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

        // Quem não vê, não altera: campos ocultos para o perfil ficam como estão
        if (! $this->veDadosClinicos()) {
            unset($dados['anamnese']);
        }
        if (! $this->veFinanceiro()) {
            unset($dados['valor_mensalidade'], $dados['forma_pagamento']);
        }

        return $dados;
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
        $this->consentimento           = false;
        $this->aceitaWhatsappMarketing = false;
        $this->aceitaEmailMarketing    = false;
        $this->pacienteEditandoId = null;
        $this->resetValidation();
    }
}
