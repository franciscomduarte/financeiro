<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Campanhas\FiltroPacientesCampanha;
use App\Actions\Campanhas\GerenciarCampanhaAction;
use App\Enums\StatusCampanha;
use App\Enums\StatusDestinatarioCampanha;
use App\Models\Campanha;
use App\Models\CampanhaDestinatario;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Services\Campanhas\RitmoCampanhas;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

/** Uma campanha: mensagem e pacientes (rascunho) ou andamento do envio (pausar, retomar, cancelar). */
class CampanhaEditor extends Component
{
    use Concerns\MensagemDeErro, WithFileUploads, WithPagination;

    #[Locked]
    public string $campanhaId;

    public string $nome = '';
    public string $mensagem = '';
    public $imagem = null;

    // Filtros da escolha de pacientes
    public string $busca = '';
    public string $aniversarioMes = '';
    public string $procedimentoId = '';
    public string $semVirDias = '';
    public string $origem = '';
    public bool $soAutorizados = false;
    public bool $soSelecionados = false;

    // Andamento
    public string $filtroStatus = '';

    public ?string $flashSucesso = null;
    public ?string $flashErro = null;

    public function mount(string $campanha): void
    {
        $c = Campanha::query()->select(['id', 'nome', 'mensagem'])->findOrFail($campanha);
        $this->campanhaId = $c->id;
        $this->nome = $c->nome;
        $this->mensagem = $c->mensagem;
    }

    public function updated(string $prop): void
    {
        if (in_array($prop, ['busca', 'aniversarioMes', 'procedimentoId', 'semVirDias', 'origem', 'soAutorizados', 'soSelecionados', 'filtroStatus'], true)) {
            $this->resetPage();
        }
    }

    public function salvar(): void
    {
        $this->limparFlash();
        $campanha = $this->rascunhoOuAviso();
        if ($campanha === null) {
            return;
        }
        $this->validate([
            'nome'     => ['required', 'string', 'max:120'],
            'mensagem' => ['required', 'string', 'max:3000'],
            'imagem'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . (int) config('campanhas.imagem_max_kb')],
        ], [], ['nome' => 'nome', 'mensagem' => 'mensagem', 'imagem' => 'imagem']);

        try {
            $dados = ['nome' => trim($this->nome), 'mensagem' => trim($this->mensagem)];
            if ($this->imagem) {
                $caminho = $this->imagem->store('campanhas/' . app(ClinicaAtual::class)->id(), 'local');
                $this->apagarImagem($campanha);
                $dados += ['imagem_path' => $caminho, 'imagem_mime' => $this->imagem->getMimeType()];
                $this->imagem = null;
            }
            $campanha->update($dados);
            $this->flashSucesso = 'Mensagem salva.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar a mensagem');
        }
    }

    public function removerImagem(): void
    {
        $this->limparFlash();
        if (($campanha = $this->rascunhoOuAviso()) === null) {
            return;
        }
        $this->apagarImagem($campanha);
        $campanha->update(['imagem_path' => null, 'imagem_mime' => null]);
        $this->imagem = null;
    }

    /** Manda a mensagem (salva) para o WhatsApp da gestão, para conferir antes de disparar. */
    public function enviarTeste(WhatsAppService $whatsapp): void
    {
        $this->limparFlash();
        $clinica = app(ClinicaAtual::class)->get();
        $campanha = $this->campanha();
        if (! $clinica?->whatsappConfigurado() || blank($clinica->whatsapp_numero)) {
            $this->flashErro = 'Para o teste, conecte o WhatsApp e informe o "WhatsApp da gestão" em Dados da clínica.';

            return;
        }

        try {
            $texto = $campanha->mensagemPara((string) (auth()->user()?->name ?? 'Teste'));
            $ok = $campanha->imagem_path
                ? $whatsapp->enviarImagemParaTelefone((string) $clinica->whatsapp_numero, base64_encode((string) Storage::disk('local')->get($campanha->imagem_path)), (string) $campanha->imagem_mime, $texto)
                : $whatsapp->enviarTextoParaTelefone((string) $clinica->whatsapp_numero, $texto);
            $ok ? $this->flashSucesso = 'Teste enviado para o WhatsApp da gestão (com a última versão salva).'
                : $this->flashErro = 'O WhatsApp não aceitou o teste. Confira a conexão em Dados da clínica.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível enviar o teste');
        }
    }

    public function alternar(string $pacienteId): void
    {
        if (($campanha = $this->rascunhoOuAviso()) === null) {
            return;
        }
        $removidos = CampanhaDestinatario::query()->where('campanha_id', $campanha->id)->where('paciente_id', $pacienteId)->delete();
        if ($removidos === 0 && Paciente::query()->whereKey($pacienteId)->whereNull('anonimizado_em')->exists()) {
            CampanhaDestinatario::query()->firstOrCreate(['campanha_id' => $campanha->id, 'paciente_id' => $pacienteId], ['status' => StatusDestinatarioCampanha::Pendente]);
        }
    }

    public function marcarTodos(FiltroPacientesCampanha $filtro): void
    {
        $this->limparFlash();
        if (($campanha = $this->rascunhoOuAviso()) === null) {
            return;
        }
        $n = $filtro->marcarTodos($campanha->id, (string) app(ClinicaAtual::class)->id(), $this->filtros());
        $this->flashSucesso = $n === 1 ? '1 paciente marcado.' : "{$n} pacientes marcados.";
    }

    public function desmarcarTodos(FiltroPacientesCampanha $filtro): void
    {
        $this->limparFlash();
        if (($campanha = $this->rascunhoOuAviso()) === null) {
            return;
        }
        $n = $filtro->desmarcarTodos($campanha->id, $this->filtros());
        $this->flashSucesso = $n === 1 ? '1 paciente desmarcado.' : "{$n} pacientes desmarcados.";
    }

    public function limparFiltros(): void
    {
        $this->reset(['busca', 'aniversarioMes', 'procedimentoId', 'semVirDias', 'origem', 'soAutorizados', 'soSelecionados']);
        $this->resetPage();
    }

    public function iniciar(GerenciarCampanhaAction $gerenciar): void
    {
        $this->acao(fn () => $gerenciar->iniciar($this->campanha()), 'Campanha iniciada: as mensagens vão saindo aos poucos.');
    }

    public function pausar(GerenciarCampanhaAction $gerenciar): void
    {
        $this->acao(fn () => $gerenciar->pausar($this->campanha()), 'Campanha pausada.');
    }

    public function retomar(GerenciarCampanhaAction $gerenciar): void
    {
        $this->acao(fn () => $gerenciar->retomar($this->campanha()), 'Campanha retomada.');
    }

    public function cancelar(GerenciarCampanhaAction $gerenciar): void
    {
        $this->acao(fn () => $gerenciar->cancelar($this->campanha()), 'Campanha cancelada: quem ainda não recebeu não vai receber.');
    }

    public function excluir(): void
    {
        $this->limparFlash();
        if (($campanha = $this->rascunhoOuAviso()) === null) {
            return;
        }
        $this->apagarImagem($campanha);
        $campanha->delete();
        $this->redirectRoute('campanhas.index', navigate: true);
    }

    public function render(FiltroPacientesCampanha $filtro, RitmoCampanhas $ritmo): View
    {
        $campanha = $this->campanha();
        $dados = ['campanha' => $campanha, 'regras' => $ritmo->descricao()];

        $selecionados = CampanhaDestinatario::query()->where('campanha_id', $campanha->id)->count();
        $dados['selecionados'] = $selecionados;

        if ($campanha->status->editavel()) {
            $pacientes = $filtro->consulta($campanha->id, $this->filtros())
                ->select(['id', 'nome', 'telefone', 'data_nascimento', 'aceita_whatsapp_marketing'])
                ->orderBy('nome')->paginate(20);
            $marcados = CampanhaDestinatario::query()->where('campanha_id', $campanha->id)
                ->whereIn('paciente_id', $pacientes->pluck('id'))->pluck('paciente_id')->flip();

            $dados += [
                'pacientes'     => $pacientes,
                'marcados'      => $marcados,
                'procedimentos' => Procedimento::query()->select(['id', 'nome'])->where('ativo', true)->orderBy('nome')->limit(200)->get(),
                'origens'       => Paciente::query()->whereNotNull('origem')->where('origem', '<>', '')->distinct()->orderBy('origem')->limit(50)->pluck('origem'),
                'estimativa'    => $ritmo->minutosEstimados($selecionados),
                'previa'        => $campanha->mensagemPara('Maria Silva'),
            ];
        } else {
            $contagem = CampanhaDestinatario::query()->where('campanha_id', $campanha->id)
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
            $dados += [
                'contagem'      => $contagem,
                'pendentes'     => (int) ($contagem[StatusDestinatarioCampanha::Pendente->value] ?? 0),
                'destinatarios' => CampanhaDestinatario::query()->where('campanha_id', $campanha->id)
                    ->with('paciente:id,nome')
                    ->select(['id', 'paciente_id', 'status', 'enviado_em', 'erro'])
                    ->when($this->filtroStatus !== '', fn ($q) => $q->where('status', $this->filtroStatus))
                    ->orderByRaw("case status when 'pendente' then 1 else 0 end")->orderByDesc('enviado_em')->orderBy('created_at')
                    ->paginate(20),
                'enviadasHoje'  => $ritmo->enviadasHoje(),
                'limite'        => $ritmo->limiteDiario(),
                'noHorario'     => $ritmo->dentroDoHorario(),
                'estimativa'    => $ritmo->minutosEstimados((int) ($contagem[StatusDestinatarioCampanha::Pendente->value] ?? 0)),
            ];
        }

        return view('livewire.campanha-editor', $dados)->layout('layouts.app', ['title' => $campanha->nome]);
    }

    private function campanha(): Campanha
    {
        return Campanha::query()
            ->select(['id', 'nome', 'mensagem', 'imagem_path', 'imagem_mime', 'status', 'total', 'enviados', 'falhas', 'iniciada_em', 'concluida_em', 'created_at'])
            ->findOrFail($this->campanhaId);
    }

    /** Campanha ainda em rascunho e clínica com escrita liberada; senão avisa e devolve null. */
    private function rascunhoOuAviso(): ?Campanha
    {
        if ($this->somenteLeituraAvisado()) {
            return null;
        }
        $campanha = $this->campanha();
        if (! $campanha->status->editavel()) {
            $this->flashErro = 'A campanha já foi iniciada: não dá mais para mudar a mensagem nem os pacientes.';

            return null;
        }

        return $campanha;
    }

    private function acao(callable $fazer, string $sucesso): void
    {
        $this->limparFlash();
        if ($this->somenteLeituraAvisado()) {
            return;
        }
        try {
            $fazer();
            $this->flashSucesso = $sucesso;
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível concluir a ação');
        }
    }

    private function apagarImagem(Campanha $campanha): void
    {
        if ($campanha->imagem_path) {
            Storage::disk('local')->delete($campanha->imagem_path);
        }
    }

    private function filtros(): array
    {
        return [
            'busca' => $this->busca, 'aniversario_mes' => $this->aniversarioMes, 'procedimento_id' => $this->procedimentoId,
            'sem_vir_dias' => $this->semVirDias, 'origem' => $this->origem,
            'so_autorizados' => $this->soAutorizados, 'so_selecionados' => $this->soSelecionados,
        ];
    }

    private function limparFlash(): void
    {
        $this->flashSucesso = $this->flashErro = null;
    }
}
