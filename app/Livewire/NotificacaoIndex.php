<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Notificacoes\AcoesEmLoteNotificacoesAction;
use App\Actions\Notificacoes\SalvarConfiguracaoNotificacaoAction;
use App\Enums\CanalNotificacao;
use App\Enums\Modulo;
use App\Enums\StatusNotificacao;
use App\Enums\TipoNotificacao;
use App\Models\Notificacao;
use App\Services\NotificacaoService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/** Central de notificações: histórico do que foi enviado, agendadas e textos dos avisos automáticos. */
class NotificacaoIndex extends Component
{
    use Concerns\MensagemDeErro;
    use WithPagination;

    private const ORDENAVEIS = ['data', 'destinatario', 'canal', 'status'];

    /** Antecedências oferecidas para os lembretes (minutos => rótulo). */
    public const ANTECEDENCIAS = [
        30 => '30 minutos', 60 => '1 hora', 120 => '2 horas', 180 => '3 horas', 240 => '4 horas', 360 => '6 horas',
        720 => '12 horas', 1440 => '1 dia', 2880 => '2 dias', 4320 => '3 dias',
    ];

    #[Url(except: 'historico')]
    public string $aba = 'historico';

    #[Url(as: 'busca', except: '')]
    public string $busca = '';
    #[Url(as: 'canal', except: '')]
    public string $filtroCanal = '';
    #[Url(as: 'situacao', except: '')]
    public string $filtroStatus = '';
    #[Url(as: 'tipo', except: '')]
    public string $filtroTipo = '';
    #[Url(as: 'de', except: '')]
    public string $de = '';
    #[Url(as: 'ate', except: '')]
    public string $ate = '';
    #[Url(as: 'ordem', except: '-data')]
    public string $ordem = '-data';

    public bool $mostrarFiltros = false;

    /** @var array<int, string> */
    public array $selecionados = [];

    public ?string $verId = null;

    // ─── Configurar ─────────────────────────────────────────────
    public string $editandoTipo = '';
    public bool $cfgWhatsapp = true;
    public bool $cfgEmail = true;
    public ?int $cfgAntecedencia = null;
    public string $cfgAssunto = '';
    public string $cfgTexto = '';

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        if (! in_array($this->aba, ['historico', 'agendadas', 'configurar'], true) || ($this->aba === 'configurar' && ! $this->podeConfigurar())) {
            $this->aba = 'historico';
        }
    }

    public function updated(string $prop): void
    {
        if (in_array($prop, ['busca', 'filtroCanal', 'filtroStatus', 'filtroTipo', 'de', 'ate', 'aba'], true)) {
            $this->resetPage();
            $this->selecionados = [];
        }
    }

    public function podeConfigurar(): bool
    {
        return (bool) auth()->user()?->pode(Modulo::DadosClinica);
    }

    public function trocarAba(string $aba): void
    {
        if ($aba === 'configurar' && ! $this->podeConfigurar()) {
            return;
        }
        $this->aba = in_array($aba, ['historico', 'agendadas', 'configurar'], true) ? $aba : 'historico';
        $this->reset('selecionados', 'filtroStatus', 'editandoTipo');
        $this->resetPage();
    }

    public function ordenar(string $campo): void
    {
        if (! in_array($campo, self::ORDENAVEIS, true)) {
            return;
        }
        $this->ordem = $this->ordem === $campo ? '-' . $campo : $campo;
        $this->resetPage();
    }

    public function limparFiltros(): void
    {
        $this->reset('busca', 'filtroCanal', 'filtroStatus', 'filtroTipo', 'de', 'ate', 'selecionados');
        $this->resetPage();
    }

    #[Computed]
    public function filtrosAtivos(): int
    {
        return count(array_filter([$this->filtroCanal, $this->filtroStatus, $this->filtroTipo, $this->de, $this->ate]));
    }

    private function consulta(): Builder
    {
        $agendadas = $this->aba === 'agendadas';
        $termo     = trim($this->busca);
        $canal     = CanalNotificacao::tryFrom($this->filtroCanal);
        $status    = StatusNotificacao::tryFrom($this->filtroStatus);
        $tipo      = TipoNotificacao::tryFrom($this->filtroTipo);
        $fuso      = (string) config('clinica.fuso_horario');
        $campoData = $agendadas ? 'agendada_para' : 'created_at';

        return Notificacao::query()
            ->select(['id', 'paciente_id', 'tipo', 'canal', 'status', 'destinatario_nome', 'destino', 'assunto', 'agendada_para', 'enviada_em', 'erro', 'created_at'])
            ->when($agendadas, fn ($q) => $q->where('status', StatusNotificacao::Agendada))
            ->when(! $agendadas, fn ($q) => $q->where('status', '!=', StatusNotificacao::Agendada))
            ->when(! $agendadas && $status !== null, fn ($q) => $q->where('status', $status))
            ->when($canal !== null, fn ($q) => $q->where('canal', $canal))
            ->when($tipo !== null, fn ($q) => $q->where('tipo', $tipo))
            ->when($this->dataValida($this->de), fn ($q) => $q->where($campoData, '>=', now($fuso)->setDateFrom($this->de)->startOfDay()->utc()))
            ->when($this->dataValida($this->ate), fn ($q) => $q->where($campoData, '<=', now($fuso)->setDateFrom($this->ate)->endOfDay()->utc()))
            ->when($termo !== '', fn ($q) => $q->where(fn ($w) => $w->where('destinatario_nome', 'ilike', '%' . addcslashes($termo, '%_\\') . '%')
                ->orWhere('assunto', 'ilike', '%' . addcslashes($termo, '%_\\') . '%')));
    }

    private function dataValida(string $data): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) && checkdate((int) substr($data, 5, 2), (int) substr($data, 8, 2), (int) substr($data, 0, 4));
    }

    private function ordenada(Builder $q): Builder
    {
        $desc  = str_starts_with($this->ordem, '-');
        $campo = ltrim($this->ordem, '-');
        $dir   = $desc ? 'desc' : 'asc';
        $data  = $this->aba === 'agendadas' ? 'agendada_para' : 'created_at';

        return match ($campo) {
            'destinatario' => $q->orderBy('destinatario_nome', $dir)->orderByDesc($data),
            'canal'        => $q->orderBy('canal', $dir)->orderByDesc($data),
            'status'       => $q->orderBy('status', $dir)->orderByDesc($data),
            default        => $q->orderBy($data, $this->aba === 'agendadas' && $this->ordem === '-data' ? 'asc' : $dir),
        };
    }

    /** Seleciona/desmarca todas as linhas da página atual. @param  array<int, string>  $idsDaPagina */
    public function alternarPagina(array $idsDaPagina): void
    {
        $idsDaPagina = array_values(array_filter($idsDaPagina, 'is_string'));
        $todas       = $idsDaPagina !== [] && array_diff($idsDaPagina, $this->selecionados) === [];

        $this->selecionados = $todas
            ? array_values(array_diff($this->selecionados, $idsDaPagina))
            : array_values(array_unique([...$this->selecionados, ...$idsDaPagina]));
    }

    public function emLote(string $acao, AcoesEmLoteNotificacoesAction $lote): void
    {
        $this->flashSucesso = $this->flashErro = null;

        try {
            $total = match ($acao) {
                'cancelar'    => $lote->cancelar($this->selecionados),
                'enviarAgora' => $lote->enviarAgora($this->selecionados),
                'reenviar'    => $lote->reenviar($this->selecionados),
                default       => throw new \RuntimeException('Ação desconhecida.'),
            };
            $this->selecionados = [];
            $this->flashSucesso = match ($acao) {
                'cancelar'    => $total === 1 ? '1 notificação cancelada.' : "{$total} notificações canceladas.",
                'enviarAgora' => $total === 1 ? '1 notificação enviada agora.' : "{$total} notificações enviadas agora.",
                default       => $total === 1 ? '1 notificação reenviada.' : "{$total} notificações reenviadas.",
            };
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível concluir');
        }
    }

    public function acao(string $acao, string $id, AcoesEmLoteNotificacoesAction $lote): void
    {
        $this->selecionados = [$id];
        $this->verId        = null;
        $this->emLote($acao, $lote);
    }

    #[Computed]
    public function detalhe(): ?Notificacao
    {
        return $this->verId ? Notificacao::query()->find($this->verId) : null;
    }

    // ─── Configurar ─────────────────────────────────────────────

    public function editar(string $tipo, NotificacaoService $notificacoes): void
    {
        $t = TipoNotificacao::tryFrom($tipo);
        if ($t === null || ! $t->configuravel() || ! $this->podeConfigurar()) {
            return;
        }
        $this->resetValidation();
        $c = $notificacoes->configuracao($t);

        $this->editandoTipo    = $t->value;
        $this->cfgWhatsapp     = (bool) $c->whatsapp;
        $this->cfgEmail        = (bool) $c->email;
        $this->cfgAntecedencia = $c->antecedenciaEfetiva();
        $this->cfgAssunto      = $c->assuntoEfetivo();
        $this->cfgTexto        = $c->textoEfetivo();
    }

    public function restaurarPadrao(): void
    {
        $t = TipoNotificacao::tryFrom($this->editandoTipo);
        if ($t !== null) {
            $this->cfgAssunto      = $t->assuntoPadrao();
            $this->cfgTexto        = $t->textoPadrao();
            $this->cfgAntecedencia = $t->antecedenciaPadrao();
        }
    }

    public function salvarConfiguracao(SalvarConfiguracaoNotificacaoAction $salvar): void
    {
        $tipo = TipoNotificacao::tryFrom($this->editandoTipo);
        if ($tipo === null || ! $this->podeConfigurar()) {
            return;
        }

        $this->validate([
            'cfgWhatsapp'     => 'boolean',
            'cfgEmail'        => 'boolean',
            'cfgAntecedencia' => [Rule::requiredIf($tipo->lembrete()), 'nullable', Rule::in(array_keys(self::ANTECEDENCIAS))],
            'cfgAssunto'      => 'required|string|max:200',
            'cfgTexto'        => 'required|string|max:2000',
        ], [
            'cfgAssunto.required' => 'Escreva o assunto do e-mail.',
            'cfgTexto.required'   => 'Escreva o texto da mensagem.',
            'cfgTexto.max'        => 'O texto pode ter até 2.000 caracteres.',
        ]);

        try {
            $salvar->execute($tipo, [
                'whatsapp' => $this->cfgWhatsapp, 'email' => $this->cfgEmail, 'antecedencia_minutos' => $this->cfgAntecedencia,
                'assunto'  => $this->cfgAssunto, 'texto' => $this->cfgTexto,
            ]);
            $this->editandoTipo = '';
            $this->flashSucesso = 'Aviso "' . $tipo->label() . '" salvo.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar');
        }
    }

    public function render(NotificacaoService $notificacoes): View
    {
        $notificacoes->esquecerConfiguracoes();

        return view('livewire.notificacao-index', [
            'lista'          => $this->aba === 'configurar' ? null : $this->ordenada($this->consulta())->paginate(20),
            'qtdAgendadas'   => Notificacao::query()->where('status', StatusNotificacao::Agendada)->count(),
            'configuracoes'  => $this->aba === 'configurar'
                ? collect(TipoNotificacao::configuraveis())->map(fn (TipoNotificacao $t) => $notificacoes->configuracao($t))
                : collect(),
            'canais'         => CanalNotificacao::cases(),
            'statusHistorico' => StatusNotificacao::doHistorico(),
            'tipos'          => TipoNotificacao::cases(),
        ]);
    }
}
