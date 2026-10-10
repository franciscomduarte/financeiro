<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Leads\AtualizarLeadAction;
use App\Actions\Leads\BuscarPacienteDoLead;
use App\Actions\Leads\CriarLeadAction;
use App\Actions\Leads\EnviarWhatsAppLeadAction;
use App\Enums\EtapaLead;
use App\Enums\Modulo;
use App\Enums\OrigemLead;
use App\Enums\TipoInteracaoLead;
use App\Models\Lead;
use App\Models\Procedimento;
use App\Models\User;
use App\Services\RelatorioLeadsService;
use App\Support\ClinicaAtual;
use App\Support\Telefone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/** Gestão de leads: funil (colunas no computador, lista no celular), ficha com histórico e relatório. */
class LeadIndex extends Component
{
    use Concerns\EscolhePaciente;
    use Concerns\MensagemDeErro;

    private const POR_COLUNA = 50;

    #[Url(except: 'funil')]
    public string $aba = 'funil';

    #[Url(as: 'lead', except: '')]
    public string $leadId = '';

    #[Url(as: 'busca', except: '')]
    public string $busca = '';
    #[Url(as: 'origem', except: '')]
    public string $filtroOrigem = '';
    #[Url(as: 'responsavel', except: '')]
    public string $filtroResponsavel = '';
    public bool $soAtencao = false;

    /** Etapa mostrada no celular */
    public string $etapaCelular = 'novo';

    // ─── Formulário (novo / editar) ─────────────────────────────
    public bool $modalForm = false;
    public string $nome = '';
    public string $telefone = '';
    public string $email = '';
    public string $origem = 'instagram';
    public string $procedimentoId = '';
    public string $interesse = '';
    public string $responsavelId = '';
    public string $observacoes = '';
    public string $proximoContato = '';

    // ─── Contato ────────────────────────────────────────────────
    public string $contatoTipo = 'ligacao';
    public string $contatoTexto = '';
    public string $contatoProximo = '';

    // ─── Conversa no WhatsApp ───────────────────────────────────
    public string $resposta = '';

    // ─── Perda ──────────────────────────────────────────────────
    public ?string $perdendoId = null;
    public string $motivoPerda = '';
    public string $motivoOutro = '';

    // ─── Já é paciente ──────────────────────────────────────────
    public ?string $vinculandoId = null;

    public bool $modalLinks = false;

    // ─── Relatório ──────────────────────────────────────────────
    public string $de = '';
    public string $ate = '';

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        $this->de  = now()->startOfMonth()->toDateString();
        $this->ate = now()->toDateString();
        if (! in_array($this->aba, ['funil', 'relatorio'], true) || ($this->aba === 'relatorio' && ! $this->podeRelatorio())) {
            $this->aba = 'funil';
        }
    }

    public function podeRelatorio(): bool
    {
        return (bool) auth()->user()?->pode(Modulo::DadosClinica); // admin
    }

    // ─── Dados ──────────────────────────────────────────────────

    private function base()
    {
        $termo = trim($this->busca);
        $digitos = preg_replace('/\D/', '', $termo) ?? '';

        return Lead::query()
            ->select(['id', 'nome', 'telefone', 'email', 'origem', 'procedimento_id', 'interesse', 'etapa', 'motivo_perda', 'responsavel_id',
                'paciente_id', 'proximo_contato_em', 'primeiro_contato_em', 'ultima_interacao_em', 'created_at', 'updated_at'])
            ->with(['procedimento:id,nome', 'responsavel:id,name', 'paciente:id,nome'])
            ->when($termo !== '', fn ($q) => $q->where(fn ($w) => $w->where('nome', 'ilike', '%' . addcslashes($termo, '%_\\') . '%')
                ->orWhere('email', 'ilike', '%' . addcslashes($termo, '%_\\') . '%')
                ->when(strlen($digitos) >= 4, fn ($x) => $x->orWhereRaw("regexp_replace(coalesce(telefone,''), '\\D', '', 'g') like ?", ['%' . $digitos . '%']))))
            ->when(OrigemLead::tryFrom($this->filtroOrigem), fn ($q, $o) => $q->where('origem', $o))
            ->when($this->filtroResponsavel !== '', fn ($q) => $this->filtroResponsavel === 'nenhum' ? $q->whereNull('responsavel_id') : $q->where('responsavel_id', (int) $this->filtroResponsavel))
            ->when($this->soAtencao, fn ($q) => $q->where(fn ($w) => $w->where('proximo_contato_em', '<=', now())
                ->orWhere(fn ($n) => $n->where('etapa', EtapaLead::Novo)->whereNull('primeiro_contato_em'))));
    }

    /** @return array<string, Collection> etapa => leads */
    #[Computed]
    public function colunas(): array
    {
        $colunas = [];
        foreach (EtapaLead::cases() as $etapa) {
            $colunas[$etapa->value] = $this->base()->where('etapa', $etapa)
                ->when(! $etapa->aberta(), fn ($q) => $q->where('updated_at', '>=', now()->subDays(30)))
                ->orderByRaw('proximo_contato_em asc nulls last')->orderByDesc('created_at')
                ->limit(self::POR_COLUNA)->get();
        }

        return $colunas;
    }

    #[Computed]
    public function resumo(): array
    {
        $abertas = array_map(fn ($e) => $e->value, EtapaLead::abertas());

        return [
            'sem_resposta' => Lead::query()->where('etapa', EtapaLead::Novo)->whereNull('primeiro_contato_em')->count(),
            'atrasados'    => Lead::query()->whereIn('etapa', $abertas)->where('proximo_contato_em', '<=', now())->count(),
            'abertos'      => Lead::query()->whereIn('etapa', $abertas)->count(),
            'fechados_mes' => Lead::query()->where('etapa', EtapaLead::Fechado)->where('convertido_em', '>=', now()->startOfMonth())->count(),
        ];
    }

    #[Computed]
    public function lead(): ?Lead
    {
        return $this->leadId !== ''
            ? Lead::query()->with(['procedimento:id,nome', 'responsavel:id,name', 'paciente:id,nome', 'interacoes' => fn ($q) => $q->with('autor:id,name')->limit(100)])->find($this->leadId)
            : null;
    }

    #[Computed]
    public function procedimentos(): Collection
    {
        return Procedimento::query()->select(['id', 'nome'])->where('ativo', true)->orderBy('nome')->limit(300)->get();
    }

    #[Computed]
    public function equipe(): Collection
    {
        $clinicaId = app(ClinicaAtual::class)->id();

        return User::query()->select(['users.id', 'users.name'])
            ->join('clinica_user', 'clinica_user.user_id', '=', 'users.id')
            ->where('clinica_user.clinica_id', $clinicaId)->where('users.active', true)
            ->orderBy('users.name')->limit(100)->get();
    }

    #[Computed]
    public function assistenteAtivo(): bool
    {
        return \App\Models\AssistenteConfiguracao::query()->where('ativo', true)->exists();
    }

    /** Equipe assume a conversa: o assistente para de responder este lead. */
    public function pausarAssistente(): void
    {
        $this->alterarAssistente(['assistente_pausado_em' => now(), 'assistente_motivo' => 'Pausado por ' . (auth()->user()?->name ?? 'equipe') . '.']);
    }

    public function retomarAssistente(): void
    {
        $this->alterarAssistente(['assistente_pausado_em' => null, 'assistente_motivo' => null]);
    }

    private function alterarAssistente(array $dados): void
    {
        try {
            app(ClinicaAtual::class)->garantirEscrita();
            Lead::query()->findOrFail($this->leadId)->update($dados);
            unset($this->lead);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível alterar o assistente');
        }
    }

    /** Responder pela ficha só com a Evolution da clínica configurada. */
    #[Computed]
    public function whatsappConectado(): bool
    {
        return (bool) app(ClinicaAtual::class)->get()?->whatsappConfigurado();
    }

    /** Aviso no formulário: telefone/e-mail já é de um paciente. */
    #[Computed]
    public function pacienteExistente(): ?string
    {
        if (! $this->modalForm || $this->leadId !== '') {
            return null;
        }

        return BuscarPacienteDoLead::porContato(Telefone::chave($this->telefone), filled($this->email) ? mb_strtolower(trim($this->email)) : null)?->nome;
    }

    // ─── Ações ──────────────────────────────────────────────────

    public function abrir(string $id): void
    {
        $this->limparFlash();
        $this->leadId = $id;
        $this->reset('contatoTexto', 'contatoProximo', 'resposta');
        $this->contatoTipo = 'ligacao';
    }

    public function fechar(): void
    {
        $this->leadId = '';
        $this->modalForm = false;
    }

    public function novo(): void
    {
        if ($this->somenteLeituraAvisado()) {
            return;
        }
        $this->limparFlash();
        $this->resetValidation();
        $this->reset('leadId', 'nome', 'telefone', 'email', 'procedimentoId', 'interesse', 'observacoes', 'proximoContato');
        $this->origem        = OrigemLead::Instagram->value;
        $this->responsavelId = (string) auth()->id();
        $this->modalForm     = true;
    }

    public function editar(): void
    {
        if ($this->somenteLeituraAvisado()) {
            return;
        }
        $l = $this->lead;
        if ($l === null) {
            return;
        }
        $this->resetValidation();
        $this->nome           = $l->nome;
        $this->telefone       = (string) $l->telefone;
        $this->email          = (string) $l->email;
        $this->origem         = $l->origem->value;
        $this->procedimentoId = (string) $l->procedimento_id;
        $this->interesse      = (string) $l->interesse;
        $this->responsavelId  = (string) $l->responsavel_id;
        $this->observacoes    = (string) $l->observacoes;
        $this->modalForm      = true;
    }

    public function salvar(CriarLeadAction $criar, AtualizarLeadAction $atualizar): void
    {
        $this->validate([
            'nome'           => 'required|string|max:150',
            'telefone'       => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:150',
            'origem'         => ['required', Rule::enum(OrigemLead::class)],
            'interesse'      => 'nullable|string|max:255',
            'observacoes'    => 'nullable|string|max:5000',
            'proximoContato' => 'nullable|date',
        ], ['nome.required' => 'Informe o nome.', 'email.email' => 'Confira o e-mail.']);

        if (filled($this->telefone) && Telefone::chave($this->telefone) === null) {
            $this->addError('telefone', 'Use o telefone com DDD. Ex.: (61) 99999-0000');

            return;
        }

        $dados = [
            'nome' => $this->nome, 'telefone' => $this->telefone, 'email' => $this->email, 'origem' => $this->origem,
            'procedimento_id' => $this->procedimentoId, 'interesse' => $this->interesse, 'observacoes' => $this->observacoes,
            'responsavel_id' => $this->responsavelId !== '' ? (int) $this->responsavelId : null, 'proximo_contato_em' => $this->proximoContato ?: null,
        ];

        try {
            if ($this->leadId !== '') {
                $atualizar->salvar($this->leadId, $dados);
                $this->flashSucesso = 'Lead atualizado.';
            } else {
                app(ClinicaAtual::class)->garantirEscrita();
                [$lead, $novo] = $criar->execute($dados, OrigemLead::from($this->origem));
                $this->leadId = $lead->id;
                $this->flashSucesso = $novo ? 'Lead cadastrado.' : 'Já existia um lead em aberto com este contato: abrimos a ficha dele.';
            }
            $this->modalForm = false;
            unset($this->colunas, $this->resumo, $this->lead);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar');
        }
    }

    /** Arrastar o cartão ou escolher a etapa na ficha. */
    public function mover(string $id, string $etapa, AtualizarLeadAction $atualizar): void
    {
        $this->limparFlash();
        $destino = EtapaLead::tryFrom($etapa);
        if ($destino === null) {
            return;
        }
        if ($destino === EtapaLead::Perdido) {
            $this->perdendoId = $id;
            $this->reset('motivoPerda', 'motivoOutro');

            return;
        }
        if ($destino === EtapaLead::Fechado) {
            $this->leadId = $id;
            $this->flashErro = 'Para fechar, abra o lead e use "Converter em paciente".';

            return;
        }
        if ($destino === EtapaLead::JaPaciente) {
            $this->abrirVinculo($id);

            return;
        }

        try {
            $atualizar->moverEtapa($id, $destino);
            unset($this->colunas, $this->resumo, $this->lead);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível mover');
        }
    }

    /** "Já é paciente": escolhe a ficha (já sugere a que tem o mesmo telefone ou e-mail). */
    public function abrirVinculo(string $id): void
    {
        $this->limparFlash();
        if ($this->somenteLeituraAvisado()) {
            return;
        }
        $lead = Lead::query()->select(['id', 'telefone_chave', 'email', 'paciente_id'])->findOrFail($id);

        $this->resetValidation();
        $this->reset('buscaPaciente', 'pacienteId', 'pacienteNome');
        $sugestao = $lead->paciente_id ?? BuscarPacienteDoLead::porContato($lead->telefone_chave, $lead->email)?->id;
        if ($sugestao !== null) {
            $this->escolherPaciente($sugestao);
        }
        $this->vinculandoId = $lead->id;
    }

    public function confirmarVinculo(AtualizarLeadAction $atualizar): void
    {
        $this->validate(['pacienteId' => ['required', 'uuid']], ['pacienteId.required' => 'Escolha o paciente na lista.']);

        try {
            $atualizar->vincularPaciente((string) $this->vinculandoId, $this->pacienteId);
            $this->flashSucesso = "Lead ligado à ficha de {$this->pacienteNome}. Ele saiu do funil.";
            $this->vinculandoId = null;
            unset($this->colunas, $this->resumo, $this->lead);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível ligar ao paciente');
        }
    }

    public function confirmarPerda(AtualizarLeadAction $atualizar): void
    {
        $motivo = $this->motivoPerda === 'Outro' ? trim($this->motivoOutro) : $this->motivoPerda;
        if ($motivo === '') {
            $this->addError('motivoPerda', 'Escolha o motivo.');

            return;
        }

        try {
            $atualizar->moverEtapa((string) $this->perdendoId, EtapaLead::Perdido, $motivo);
            $this->perdendoId = null;
            $this->flashSucesso = 'Lead marcado como perdido.';
            unset($this->colunas, $this->resumo, $this->lead);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar');
        }
    }

    public function registrarContato(AtualizarLeadAction $atualizar): void
    {
        $this->validate([
            'contatoTipo'    => ['required', Rule::in(['ligacao', 'whatsapp_enviado', 'nota'])],
            'contatoTexto'   => ['nullable', 'string', 'max:2000'],
            'contatoProximo' => ['nullable', 'date'],
        ]);
        if ($this->contatoTipo === 'nota' && trim($this->contatoTexto) === '') {
            $this->addError('contatoTexto', 'Escreva a anotação.');

            return;
        }

        try {
            $atualizar->registrarContato($this->leadId, TipoInteracaoLead::from($this->contatoTipo), $this->contatoTexto, $this->contatoProximo ?: null);
            $this->reset('contatoTexto', 'contatoProximo');
            $this->flashSucesso = 'Contato registrado.';
            unset($this->colunas, $this->resumo, $this->lead);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível registrar');
        }
    }

    /** Resposta enviada pela própria ficha, pelo WhatsApp da clínica. */
    public function enviarWhatsApp(EnviarWhatsAppLeadAction $enviar): void
    {
        $this->limparFlash();
        $this->validate(['resposta' => ['required', 'string', 'max:2000']], ['resposta.required' => 'Escreva a mensagem.']);

        try {
            $enviar->execute($this->leadId, $this->resposta);
            $this->reset('resposta');
            unset($this->colunas, $this->resumo, $this->lead);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível enviar');
        }
    }

    /** Botão de WhatsApp da ficha: abre a conversa (no navegador) e registra o envio. */
    public function whatsappAberto(AtualizarLeadAction $atualizar): void
    {
        try {
            $atualizar->registrarContato($this->leadId, TipoInteracaoLead::WhatsAppEnviado, 'Conversa aberta pelo botão de WhatsApp.', $this->lead?->proximo_contato_em?->format('Y-m-d H:i:s'));
            unset($this->colunas, $this->resumo, $this->lead);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível registrar');
        }
    }

    public function converter(AtualizarLeadAction $atualizar): mixed
    {
        try {
            $paciente = $atualizar->converter($this->leadId);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível converter');

            return null;
        }

        session()->flash('success', "{$paciente->nome} agora é paciente. Agende a avaliação.");

        return auth()->user()->pode(Modulo::Agenda)
            ? $this->redirectRoute('agenda.index', ['paciente' => $paciente->id], navigate: true)
            : $this->redirectRoute('pacientes.index', navigate: true);
    }

    public function textoWhatsApp(Lead $lead): string
    {
        $primeiro  = explode(' ', trim($lead->nome))[0];
        $interesse = $lead->procedimento?->nome ?? $lead->interesse;

        return "Olá, {$primeiro}! Aqui é da " . app(ClinicaAtual::class)->nome() . '. '
            . ($interesse ? "Vi seu interesse em {$interesse}. " : 'Recebemos seu contato. ')
            . 'Posso te ajudar a agendar uma avaliação?';
    }

    private function limparFlash(): void
    {
        $this->flashSucesso = $this->flashErro = null;
    }

    public function render(RelatorioLeadsService $relatorio): View
    {
        $de  = rescue(fn () => now()->setDateFrom($this->de), now()->startOfMonth(), false);
        $ate = rescue(fn () => now()->setDateFrom($this->ate), now(), false);

        return view('livewire.lead-index', [
            'etapas'     => EtapaLead::cases(),
            'origens'    => OrigemLead::cases(),
            'relatorio'  => $this->aba === 'relatorio' && $this->podeRelatorio() ? $relatorio->gerar($de, $ate) : null,
            'linkBase'   => route('leads.formulario', app(ClinicaAtual::class)->get()?->slug ?? 'x'),
        ]);
    }
}
