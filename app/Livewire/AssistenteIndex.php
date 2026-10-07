<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Leads\ResponderLeadAction;
use App\Models\AssistenteConfiguracao;
use App\Models\AssistenteConhecimento;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/** Assistente do WhatsApp: liga/desliga, regras, treinamento (o que ele sabe) e teste da conversa. */
class AssistenteIndex extends Component
{
    use Concerns\MensagemDeErro;

    // ─── Configuração ───────────────────────────────────────────
    public bool $ativo = false;
    public string $nome = 'Assistente';
    public string $instrucoes = '';
    public bool $informarPrecos = true;
    public bool $podeAgendar = true;
    public string $procedimentoAvaliacaoId = '';
    public string $profissionalId = '';
    public int $limiteRespostasMes = 500;

    // ─── Treinamento ────────────────────────────────────────────
    public bool $modalConhecimento = false;
    public ?string $conhecimentoId = null;
    public string $titulo = '';
    public string $conteudo = '';

    // ─── Teste ──────────────────────────────────────────────────
    /** @var list<array{role: string, content: string}> */
    public array $teste = [];
    public string $perguntaTeste = '';

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        $c = AssistenteConfiguracao::atual();
        $this->ativo                   = $c->ativo;
        $this->nome                    = $c->nome;
        $this->instrucoes              = (string) $c->instrucoes;
        $this->informarPrecos          = $c->informar_precos;
        $this->podeAgendar             = $c->pode_agendar;
        $this->procedimentoAvaliacaoId = (string) $c->procedimento_avaliacao_id;
        $this->profissionalId          = (string) $c->profissional_id;
        $this->limiteRespostasMes      = $c->limite_respostas_mes;
    }

    #[Computed]
    public function config(): AssistenteConfiguracao
    {
        return AssistenteConfiguracao::atual();
    }

    #[Computed]
    public function conhecimentos(): Collection
    {
        return AssistenteConhecimento::query()->select(['id', 'titulo', 'conteudo', 'ativo'])->orderBy('titulo')->limit(200)->get();
    }

    #[Computed]
    public function procedimentos(): Collection
    {
        return Procedimento::query()->select(['id', 'nome', 'duracao_minutos'])->where('ativo', true)->orderBy('nome')->limit(300)->get();
    }

    #[Computed]
    public function profissionais(): Collection
    {
        return Profissional::query()->select(['id', 'nome'])->where('ativo', true)->orderBy('nome')->limit(100)->get();
    }

    /** O que falta para o assistente funcionar. @return list<string> */
    #[Computed]
    public function pendencias(): array
    {
        $clinica = app(ClinicaAtual::class)->get();

        return array_values(array_filter([
            blank(config('services.anthropic.key')) ? 'A chave da IA (ANTHROPIC_API_KEY) não está configurada no servidor.' : null,
            ! $clinica?->whatsappConfigurado() ? 'O WhatsApp da clínica não está conectado (Dados da clínica).' : null,
            $this->conhecimentos->where('ativo', true)->isEmpty() ? 'Cadastre pelo menos um item de treinamento abaixo.' : null,
        ]));
    }

    public function salvar(): void
    {
        $this->limparFlash();
        $this->validate([
            'nome'                    => 'required|string|max:60',
            'instrucoes'              => 'nullable|string|max:5000',
            'procedimentoAvaliacaoId' => ['nullable', Rule::exists('procedimentos', 'id')],
            'profissionalId'          => ['nullable', Rule::exists('profissionais', 'id')],
            'limiteRespostasMes'      => 'required|integer|min:0|max:100000',
        ], ['nome.required' => 'Dê um nome ao assistente.']);

        if ($this->podeAgendar && $this->procedimentoAvaliacaoId === '') {
            $this->addError('procedimentoAvaliacaoId', 'Escolha o procedimento da avaliação para o assistente poder agendar.');

            return;
        }

        try {
            app(ClinicaAtual::class)->garantirEscrita();
            AssistenteConfiguracao::atual()->update([
                'ativo'                     => $this->ativo,
                'nome'                      => trim($this->nome),
                'instrucoes'                => trim($this->instrucoes) ?: null,
                'informar_precos'           => $this->informarPrecos,
                'pode_agendar'              => $this->podeAgendar,
                'procedimento_avaliacao_id' => $this->procedimentoAvaliacaoId !== '' ? (int) $this->procedimentoAvaliacaoId : null,
                'profissional_id'           => $this->profissionalId ?: null,
                'limite_respostas_mes'      => $this->limiteRespostasMes,
            ]);
            unset($this->config);
            $this->flashSucesso = $this->ativo ? 'Assistente ligado. Ele vai responder as próximas mensagens dos leads.' : 'Configuração salva. O assistente está desligado.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar');
        }
    }

    // ─── Treinamento ────────────────────────────────────────────

    public function novoConhecimento(): void
    {
        $this->resetValidation();
        $this->reset('conhecimentoId', 'titulo', 'conteudo');
        $this->modalConhecimento = true;
    }

    public function editarConhecimento(string $id): void
    {
        $c = AssistenteConhecimento::query()->findOrFail($id);
        $this->resetValidation();
        $this->conhecimentoId = $c->id;
        $this->titulo         = $c->titulo;
        $this->conteudo       = $c->conteudo;
        $this->modalConhecimento = true;
    }

    public function salvarConhecimento(): void
    {
        $this->validate([
            'titulo'   => 'required|string|max:120',
            'conteudo' => 'required|string|max:8000',
        ], ['titulo.required' => 'Dê um título.', 'conteudo.required' => 'Escreva o conteúdo.']);

        try {
            app(ClinicaAtual::class)->garantirEscrita();
            $dados = ['titulo' => trim($this->titulo), 'conteudo' => trim($this->conteudo)];
            $this->conhecimentoId
                ? AssistenteConhecimento::query()->findOrFail($this->conhecimentoId)->update($dados)
                : AssistenteConhecimento::create($dados + ['ativo' => true]);
            $this->modalConhecimento = false;
            $this->flashSucesso = 'Treinamento salvo.';
            unset($this->conhecimentos, $this->pendencias);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar');
        }
    }

    public function alternarConhecimento(string $id): void
    {
        $c = AssistenteConhecimento::query()->findOrFail($id);
        $c->update(['ativo' => ! $c->ativo]);
        unset($this->conhecimentos, $this->pendencias);
    }

    public function excluirConhecimento(string $id): void
    {
        AssistenteConhecimento::query()->findOrFail($id)->delete();
        unset($this->conhecimentos, $this->pendencias);
    }

    // ─── Teste ──────────────────────────────────────────────────

    public function testar(ResponderLeadAction $responder): void
    {
        $this->limparFlash();
        $this->validate(['perguntaTeste' => 'required|string|max:1000'], ['perguntaTeste.required' => 'Escreva uma mensagem de teste.']);
        if (blank(config('services.anthropic.key')) && ! app()->runningUnitTests()) {
            $this->flashErro = 'A chave da IA (ANTHROPIC_API_KEY) não está configurada no servidor.';

            return;
        }

        $historico   = [...array_slice($this->teste, -19), ['role' => 'user', 'content' => trim($this->perguntaTeste)]];
        try {
            $resposta = $responder->simular($historico);
            $texto    = $resposta->recusou ? '(O assistente não respondeu esta mensagem e passaria para a equipe.)' : ($resposta->texto ?: '(Sem resposta: passaria para a equipe.)');
            $this->teste = [...$historico, ['role' => 'assistant', 'content' => $texto]];
            $this->perguntaTeste = '';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível testar agora');
        }
    }

    public function limparTeste(): void
    {
        $this->reset('teste', 'perguntaTeste');
    }

    private function limparFlash(): void
    {
        $this->flashSucesso = $this->flashErro = null;
    }

    public function render(): View
    {
        return view('livewire.assistente-index');
    }
}
