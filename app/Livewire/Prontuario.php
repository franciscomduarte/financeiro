<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Prontuario\AdicionarFotosProntuarioAction;
use App\Actions\Prontuario\AssinarTermoAction;
use App\Actions\Prontuario\EnviarOrientacaoWhatsAppAction;
use App\Actions\Prontuario\RegistrarEvolucaoAction;
use App\Actions\Prontuario\RegistrarOrientacaoAction;
use App\Actions\Prontuario\RemoverFotoProntuarioAction;
use App\Enums\AcaoAcessoPaciente;
use App\Enums\MomentoFoto;
use App\Enums\TipoModeloProntuario;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\ProntuarioEvolucao;
use App\Models\ProntuarioFoto;
use App\Models\ProntuarioModelo;
use App\Models\ProntuarioOrientacao;
use App\Models\ProntuarioTermo;
use App\Services\RegistroAcessoPaciente;
use App\Support\PreencherModeloProntuario;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/** Prontuário do paciente: evoluções, fotos, termos assinados e orientações (admin e profissional). */
class Prontuario extends Component
{
    use Concerns\MensagemDeErro;
    use WithFileUploads;

    public const ABAS = ['atendimentos', 'evolucoes', 'fotos', 'termos', 'orientacoes'];
    private const POR_PAGINA = 20;

    #[Locked]
    public string $pacienteId;

    #[Url(except: 'atendimentos')]
    public string $aba = 'atendimentos';

    public int $limite = self::POR_PAGINA;

    // ─── Evolução ───────────────────────────────────────────────
    public string $evolucaoTexto = '';
    public string $evolucaoAgendamentoId = '';

    // ─── Fotos ──────────────────────────────────────────────────
    /** @var array<int, mixed> */
    public array $fotos = [];
    public string $fotoMomento = 'antes';
    public string $fotoTiradaEm = '';
    public string $fotoRegiao = '';
    public string $fotoDescricao = '';
    public string $fotoAgendamentoId = '';
    /** @var array<int, string> fotos marcadas para comparar (até 2) */
    public array $comparar = [];
    public bool $modalComparar = false;

    // ─── Termo ──────────────────────────────────────────────────
    public bool $modalTermo = false;
    public string $termoModeloId = '';
    public string $termoTitulo = '';
    public string $termoConteudo = '';
    public string $termoAssinante = '';
    public string $termoAgendamentoId = '';

    // ─── Orientação ─────────────────────────────────────────────
    public bool $modalOrientacao = false;
    public string $orientacaoModeloId = '';
    public string $orientacaoTitulo = '';
    public string $orientacaoTexto = '';
    public string $orientacaoAgendamentoId = '';
    public bool $orientacaoWhatsapp = false;

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(string $id, RegistroAcessoPaciente $registro): void
    {
        $paciente = Paciente::query()->select(['id', 'tenant_id'])->findOrFail($id);

        $this->pacienteId   = $paciente->id;
        $this->fotoTiradaEm = now()->toDateString();
        $this->aba          = in_array($this->aba, self::ABAS, true) ? $this->aba : 'atendimentos';

        $registro->registrar($paciente, AcaoAcessoPaciente::AbriuProntuario);
    }

    public function updatedAba(): void
    {
        $this->aba    = in_array($this->aba, self::ABAS, true) ? $this->aba : 'atendimentos';
        $this->limite = self::POR_PAGINA;
        $this->limparFlash();
    }

    public function verMais(): void
    {
        $this->limite += self::POR_PAGINA;
    }

    // ─── Dados ──────────────────────────────────────────────────

    #[Computed]
    public function paciente(): Paciente
    {
        return Paciente::query()
            ->select(['id', 'tenant_id', 'nome', 'cpf', 'data_nascimento', 'telefone', 'anamnese', 'foto_path', 'anonimizado_em'])
            ->findOrFail($this->pacienteId);
    }

    /** Atendimentos recentes do paciente, para ligar registros a um atendimento. */
    #[Computed]
    public function atendimentos(): Collection
    {
        return Agendamento::query()
            ->select(['id', 'paciente_id', 'profissional_id', 'procedimento_id', 'inicio_em'])
            ->with(['procedimento:id,nome', 'profissional:id,nome'])
            ->where('paciente_id', $this->pacienteId)
            ->where('inicio_em', '<=', now()->addDays(30))
            ->orderByDesc('inicio_em')
            ->limit(30)
            ->get();
    }

    /** @return array<string, int> */
    #[Computed]
    public function contagens(): array
    {
        return [
            'atendimentos' => \App\Models\Atendimento::query()->where('paciente_id', $this->pacienteId)->count(),
            'evolucoes'   => ProntuarioEvolucao::query()->where('paciente_id', $this->pacienteId)->count(),
            'fotos'       => ProntuarioFoto::query()->where('paciente_id', $this->pacienteId)->count(),
            'termos'      => ProntuarioTermo::query()->where('paciente_id', $this->pacienteId)->count(),
            'orientacoes' => ProntuarioOrientacao::query()->where('paciente_id', $this->pacienteId)->count(),
        ];
    }

    #[Computed]
    public function registrosAtendimento(): Collection
    {
        return \App\Models\Atendimento::query()
            ->with([
                'profissional:id,nome', 'autor:id,name', 'agendamento:id,inicio_em,procedimento_id', 'agendamento.procedimento:id,nome',
                'fichas:id,atendimento_id,titulo,campos,respostas', 'injetaveis.produto:id,name,unit_type', 'plano.itens',
                'anexos:id,atendimento_id,nome,tamanho',
            ])
            ->where('paciente_id', $this->pacienteId)
            ->latest('iniciado_em')
            ->limit($this->limite)
            ->get();
    }

    /** PDFs do paciente (exames, laudos), mostrados na aba Fotos. */
    #[Computed]
    public function anexos(): Collection
    {
        return \App\Models\ProntuarioAnexo::query()->select(['id', 'paciente_id', 'atendimento_id', 'nome', 'tamanho', 'created_at'])
            ->where('paciente_id', $this->pacienteId)
            ->where(fn ($q) => $q->whereNull('atendimento_id')->orWhereIn('atendimento_id', \App\Models\Atendimento::query()->select('id'))) // respeita atendimento privado
            ->latest()->limit(100)->get();
    }

    /** Abre um atendimento avulso (sem agendamento) para este paciente. */
    public function novoAtendimento(\App\Actions\Atendimento\IniciarAtendimentoAction $iniciar): mixed
    {
        try {
            $a = $iniciar->execute($this->pacienteId);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível iniciar o atendimento');

            return null;
        }

        return $this->redirectRoute('atendimentos.show', ['id' => $a->id], navigate: true);
    }

    #[Computed]
    public function evolucoes(): Collection
    {
        return ProntuarioEvolucao::query()
            ->select(['id', 'paciente_id', 'agendamento_id', 'profissional_id', 'user_id', 'texto', 'created_at'])
            ->with(['autor:id,name', 'profissional:id,nome', 'agendamento:id,inicio_em,procedimento_id', 'agendamento.procedimento:id,nome'])
            ->where('paciente_id', $this->pacienteId)
            ->latest()
            ->limit($this->limite)
            ->get();
    }

    #[Computed]
    public function fotosSalvas(): Collection
    {
        return ProntuarioFoto::query()
            ->select(['id', 'paciente_id', 'user_id', 'momento', 'regiao', 'descricao', 'tirada_em', 'created_at'])
            ->with('autor:id,name')
            ->where('paciente_id', $this->pacienteId)
            ->orderByDesc('tirada_em')->orderByDesc('created_at')
            ->limit($this->limite)
            ->get();
    }

    #[Computed]
    public function fotosComparadas(): Collection
    {
        return ProntuarioFoto::query()
            ->select(['id', 'paciente_id', 'momento', 'regiao', 'tirada_em'])
            ->where('paciente_id', $this->pacienteId)
            ->whereIn('id', $this->comparar)
            ->orderBy('tirada_em')->orderBy('created_at')
            ->limit(2)
            ->get();
    }

    #[Computed]
    public function termos(): Collection
    {
        return ProntuarioTermo::query()
            ->select(['id', 'paciente_id', 'user_id', 'titulo', 'assinante_nome', 'assinado_em', 'hash'])
            ->with('autor:id,name')
            ->where('paciente_id', $this->pacienteId)
            ->orderByDesc('assinado_em')
            ->limit($this->limite)
            ->get();
    }

    #[Computed]
    public function orientacoes(): Collection
    {
        return ProntuarioOrientacao::query()
            ->select(['id', 'paciente_id', 'user_id', 'titulo', 'texto', 'enviada_whatsapp_em', 'created_at'])
            ->with('autor:id,name')
            ->where('paciente_id', $this->pacienteId)
            ->latest()
            ->limit($this->limite)
            ->get();
    }

    #[Computed]
    public function modelos(): Collection
    {
        return ProntuarioModelo::query()
            ->select(['id', 'tipo', 'titulo'])
            ->where('ativo', true)
            ->orderBy('titulo')
            ->limit(100)
            ->get();
    }

    // ─── Evolução ───────────────────────────────────────────────

    public function registrarEvolucao(RegistrarEvolucaoAction $registrar): void
    {
        $this->limparFlash();
        $this->validate([
            'evolucaoTexto'         => ['required', 'string', 'min:3', 'max:20000'],
            'evolucaoAgendamentoId' => ['nullable', 'uuid'],
        ], [
            'evolucaoTexto.required' => 'Escreva como foi o atendimento.',
            'evolucaoTexto.min'      => 'Escreva como foi o atendimento.',
        ]);

        if (! $this->podeRegistrar()) {
            return;
        }

        try {
            $registrar->execute($this->paciente, $this->evolucaoTexto, $this->evolucaoAgendamentoId ?: null);
            $this->reset('evolucaoTexto', 'evolucaoAgendamentoId');
            $this->flashSucesso = 'Evolução registrada.';
            unset($this->evolucoes, $this->contagens);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível registrar a evolução');
        }
    }

    // ─── Fotos ──────────────────────────────────────────────────

    public function adicionarFotos(AdicionarFotosProntuarioAction $adicionar): void
    {
        $this->limparFlash();
        $this->validate([
            'fotos'             => ['required', 'array', 'min:1', 'max:10'],
            'fotos.*'           => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'fotoMomento'       => ['required', Rule::enum(MomentoFoto::class)],
            'fotoTiradaEm'      => ['required', 'date', 'before_or_equal:today'],
            'fotoRegiao'        => ['nullable', 'string', 'max:80'],
            'fotoDescricao'     => ['nullable', 'string', 'max:255'],
            'fotoAgendamentoId' => ['nullable', 'uuid'],
        ], [
            'fotos.required'               => 'Escolha pelo menos uma foto.',
            'fotos.max'                    => 'Envie até 10 fotos por vez.',
            'fotos.*.image'                => 'Envie só imagens (JPG, PNG ou WEBP).',
            'fotos.*.mimes'                => 'Envie só imagens (JPG, PNG ou WEBP).',
            'fotos.*.max'                  => 'Cada foto pode ter até 10 MB.',
            'fotoTiradaEm.before_or_equal' => 'A data da foto não pode ser no futuro.',
        ]);

        if (! $this->podeRegistrar()) {
            return;
        }

        try {
            $salvas = $adicionar->execute(
                $this->paciente,
                $this->fotos,
                MomentoFoto::from($this->fotoMomento),
                $this->fotoTiradaEm,
                $this->fotoRegiao,
                $this->fotoDescricao,
                $this->fotoAgendamentoId ?: null,
            );
            $this->reset('fotos', 'fotoDescricao');
            $this->flashSucesso = count($salvas) === 1 ? 'Foto adicionada.' : count($salvas) . ' fotos adicionadas.';
            unset($this->fotosSalvas, $this->contagens);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível guardar as fotos');
        }
    }

    public function alternarComparacao(string $fotoId): void
    {
        if (in_array($fotoId, $this->comparar, true)) {
            $this->comparar = array_values(array_diff($this->comparar, [$fotoId]));

            return;
        }

        // Até duas: a terceira escolhida substitui a mais antiga
        $this->comparar = array_slice([...$this->comparar, $fotoId], -2);
    }

    public function abrirComparacao(): void
    {
        $this->modalComparar = count($this->comparar) === 2;
        unset($this->fotosComparadas);
    }

    public function removerFoto(string $fotoId, RemoverFotoProntuarioAction $remover): void
    {
        $this->limparFlash();

        try {
            $remover->execute(ProntuarioFoto::query()->where('paciente_id', $this->pacienteId)->findOrFail($fotoId));
            $this->comparar     = array_values(array_diff($this->comparar, [$fotoId]));
            $this->flashSucesso = 'Foto removida.';
            unset($this->fotosSalvas, $this->contagens);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível remover a foto');
        }
    }

    // ─── Termo ──────────────────────────────────────────────────

    public function abrirTermo(): void
    {
        $this->limparFlash();
        if (! $this->podeRegistrar()) {
            return;
        }

        $this->reset('termoModeloId', 'termoTitulo', 'termoConteudo', 'termoAgendamentoId');
        $this->resetValidation();
        $this->termoAssinante = $this->paciente->nome;
        $this->modalTermo     = true;
    }

    public function updatedTermoModeloId(): void
    {
        $this->preencherDoModelo('termo');
    }

    public function updatedTermoAgendamentoId(): void
    {
        $this->preencherDoModelo('termo');
    }

    public function assinarTermo(string $assinatura, AssinarTermoAction $assinar): void
    {
        $this->limparFlash();
        $this->validate([
            'termoTitulo'        => ['required', 'string', 'max:150'],
            'termoConteudo'      => ['required', 'string', 'min:20', 'max:30000'],
            'termoAssinante'     => ['required', 'string', 'max:150'],
            'termoModeloId'      => ['nullable', 'uuid'],
            'termoAgendamentoId' => ['nullable', 'uuid'],
        ], [
            'termoTitulo.required'    => 'Dê um título ao termo.',
            'termoConteudo.required'  => 'Escolha um modelo ou escreva o texto do termo.',
            'termoConteudo.min'       => 'O texto do termo está curto demais.',
            'termoAssinante.required' => 'Informe o nome de quem assina.',
        ]);

        if (! $this->podeRegistrar()) {
            return;
        }

        try {
            $assinar->execute(
                $this->paciente,
                $this->termoTitulo,
                $this->termoConteudo,
                $this->termoAssinante,
                $assinatura,
                $this->termoModeloId ?: null,
                $this->termoAgendamentoId ?: null,
            );
            $this->modalTermo   = false;
            $this->aba          = 'termos';
            $this->flashSucesso = 'Termo assinado e guardado no prontuário.';
            unset($this->termos, $this->contagens);
        } catch (Throwable $e) {
            $this->addError('assinatura', $this->mensagemDeErro($e, 'Não foi possível guardar o termo'));
        }
    }

    // ─── Orientação ─────────────────────────────────────────────

    public function abrirOrientacao(): void
    {
        $this->limparFlash();
        if (! $this->podeRegistrar()) {
            return;
        }

        $this->reset('orientacaoModeloId', 'orientacaoTitulo', 'orientacaoTexto', 'orientacaoAgendamentoId');
        $this->resetValidation();
        $this->orientacaoWhatsapp = (bool) $this->paciente->telefone;
        $this->modalOrientacao    = true;
    }

    public function updatedOrientacaoModeloId(): void
    {
        $this->preencherDoModelo('orientacao');
    }

    public function updatedOrientacaoAgendamentoId(): void
    {
        $this->preencherDoModelo('orientacao');
    }

    public function salvarOrientacao(RegistrarOrientacaoAction $registrar, EnviarOrientacaoWhatsAppAction $enviar): void
    {
        $this->limparFlash();
        $this->validate([
            'orientacaoTitulo'        => ['required', 'string', 'max:150'],
            'orientacaoTexto'         => ['required', 'string', 'min:5', 'max:20000'],
            'orientacaoAgendamentoId' => ['nullable', 'uuid'],
        ], [
            'orientacaoTitulo.required' => 'Dê um título às orientações.',
            'orientacaoTexto.required'  => 'Escolha um modelo ou escreva as orientações.',
        ]);

        if (! $this->podeRegistrar()) {
            return;
        }

        try {
            $orientacao = $registrar->execute($this->paciente, $this->orientacaoTitulo, $this->orientacaoTexto, $this->orientacaoAgendamentoId ?: null);
        } catch (Throwable $e) {
            $this->addError('orientacaoTexto', $this->mensagemDeErro($e, 'Não foi possível salvar as orientações'));

            return;
        }

        $this->modalOrientacao = false;
        $this->aba             = 'orientacoes';
        unset($this->orientacoes, $this->contagens);
        $this->flashSucesso = 'Orientações salvas.';

        if ($this->orientacaoWhatsapp) {
            $this->enviarOrientacao($orientacao->id, $enviar);
        }
    }

    public function enviarOrientacao(string $orientacaoId, EnviarOrientacaoWhatsAppAction $enviar): void
    {
        try {
            $enviar->execute(ProntuarioOrientacao::query()->where('paciente_id', $this->pacienteId)->findOrFail($orientacaoId));
            $this->flashSucesso = 'Orientações enviadas por WhatsApp.';
            unset($this->orientacoes);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível enviar por WhatsApp');
        }
    }

    // ─── Apoio ──────────────────────────────────────────────────

    /** Escolher um modelo (ou trocar o atendimento) preenche título e texto com os dados do paciente. */
    private function preencherDoModelo(string $tipo): void
    {
        $modeloId = $tipo === 'termo' ? $this->termoModeloId : $this->orientacaoModeloId;
        if ($modeloId === '') {
            return;
        }

        $modelo = ProntuarioModelo::query()->select(['id', 'tipo', 'titulo', 'conteudo'])
            ->where('tipo', $tipo === 'termo' ? TipoModeloProntuario::Termo : TipoModeloProntuario::Orientacao)
            ->find($modeloId);
        if ($modelo === null) {
            return;
        }

        $agendamentoId = $tipo === 'termo' ? $this->termoAgendamentoId : $this->orientacaoAgendamentoId;
        $atendimento   = $agendamentoId !== '' ? $this->atendimentos->firstWhere('id', $agendamentoId) : null;
        $texto         = app(PreencherModeloProntuario::class)->preencher($modelo->conteudo, $this->paciente, $atendimento);

        if ($tipo === 'termo') {
            $this->termoTitulo   = $modelo->titulo;
            $this->termoConteudo = $texto;
        } else {
            $this->orientacaoTitulo = $modelo->titulo;
            $this->orientacaoTexto  = $texto;
        }
    }

    private function podeRegistrar(): bool
    {
        if ($this->paciente->anonimizado()) {
            $this->flashErro = 'Este paciente foi anonimizado. O prontuário fica guardado, mas não recebe novos registros.';

            return false;
        }

        return true;
    }

    private function limparFlash(): void
    {
        $this->flashSucesso = $this->flashErro = null;
    }

    public function render(): View
    {
        return view('livewire.prontuario', [
            'momentos' => MomentoFoto::cases(),
            'ehAdmin'  => (bool) auth()->user()?->isAdmin(),
        ])->layout('layouts.app', ['title' => 'Prontuário']);
    }
}
