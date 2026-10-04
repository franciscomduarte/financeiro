<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\BloqueioAgenda;
use App\Models\GradeHorario;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Services\BloqueioAgendaService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class AgendamentoConfiguracaoIndex extends Component
{
    // ─── Aba ──────────────────────────────────────────────────────
    public string $aba = 'profissionais';

    // ─── Flash ────────────────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    // ─── Modal: Profissional ──────────────────────────────────────
    public bool    $modalProfissional      = false;
    public ?string $profissionalEditandoId = null;
    public string  $profNome      = '';
    public string  $profEmail     = '';
    public string  $profTelefone  = '';
    public string  $profCor       = '#be123c';
    public bool    $profAtivo     = true;

    // ─── Modal: Grade Horária ─────────────────────────────────────
    public bool    $modalGrade              = false;
    public ?string $gradeEditandoId         = null;
    public string  $gradeEditandoNome       = '';
    /** @var array<int, array{hora_inicio: string, hora_fim: string, ativo: bool, tem_intervalo: bool, intervalo_inicio: string, intervalo_fim: string}> */
    public array $grade = [];

    // ─── Modal: Procedimento ──────────────────────────────────────
    public bool    $modalProcedimento      = false;
    public ?int    $procedimentoEditandoId = null;
    public string  $procNome      = '';
    public string  $procDescricao = '';
    public string  $procDuracao   = '60';
    public string  $procValor     = '';
    public bool    $procAtivo     = true;

    // ─── Modal: Bloqueio ──────────────────────────────────────────
    public const BLOQUEIO_TODOS = 'todos';

    public bool   $modalBloqueio       = false;
    public string $bloqProfissionalId  = self::BLOQUEIO_TODOS;
    public bool   $bloqDiaInteiro      = true;
    public string $bloqDataInicio      = '';
    public string $bloqDataFim         = '';
    public string $bloqHoraInicio      = '08:00';
    public string $bloqHoraFim         = '12:00';
    public string $bloqMotivo          = '';

    /**
     * Agendamentos pendentes dentro do último bloqueio criado (aviso para reagendar/cancelar).
     *
     * @var list<array{data: string, horario: string, paciente: string, profissional: string, profissional_id: string}>
     */
    public array  $bloqConflitos       = [];
    public string $bloqConflitosResumo = '';

    // ─── Profissional: modais ─────────────────────────────────────
    public function abrirModalNovoProfissional(): void
    {
        $this->resetProfissionalForm();
        $this->profissionalEditandoId = null;
        $this->modalProfissional      = true;
    }

    public function abrirModalEditarProfissional(string $id): void
    {
        $p = Profissional::findOrFail($id);
        $this->profissionalEditandoId = $id;
        $this->profNome     = $p->nome;
        $this->profEmail    = $p->email;
        $this->profTelefone = $p->telefone ?? '';
        $this->profCor      = $p->cor_agenda;
        $this->profAtivo    = $p->ativo;
        $this->modalProfissional = true;
    }

    public function fecharModalProfissional(): void
    {
        $this->modalProfissional = false;
        $this->resetProfissionalForm();
    }

    public function salvarProfissional(): void
    {
        $this->validate([
            'profNome'     => 'required|string|max:150',
            'profEmail'    => 'required|email|max:200',
            'profTelefone' => 'nullable|string|max:20',
            'profCor'      => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        try {
            $data = [
                'nome'      => $this->profNome,
                'email'     => $this->profEmail,
                'telefone'  => $this->profTelefone ?: null,
                'cor_agenda' => $this->profCor ?: '#be123c',
                'ativo'     => $this->profAtivo,
            ];

            if ($this->profissionalEditandoId) {
                Profissional::findOrFail($this->profissionalEditandoId)->update($data);
                $this->flashSucesso = 'Profissional atualizado.';
            } else {
                Profissional::create($data);
                $this->flashSucesso = 'Profissional criado.';
            }
            $this->modalProfissional = false;
            $this->resetProfissionalForm();
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao salvar profissional: ' . $e->getMessage();
        }
    }

    private function resetProfissionalForm(): void
    {
        $this->profNome     = '';
        $this->profEmail    = '';
        $this->profTelefone = '';
        $this->profCor      = '#be123c';
        $this->profAtivo    = true;
        $this->resetErrorBag();
    }

    // ─── Grade Horária ────────────────────────────────────────────
    public function abrirModalGrade(string $id): void
    {
        $profissional             = Profissional::findOrFail($id);
        $this->gradeEditandoId    = $id;
        $this->gradeEditandoNome  = $profissional->nome;

        $existente = GradeHorario::where('profissional_id', $id)
            ->get()
            ->keyBy('dia_semana');

        $this->grade = [];
        $hora = fn (?string $valor, string $padrao) => $valor ? substr($valor, 0, 5) : $padrao;

        for ($dia = 0; $dia <= 6; $dia++) {
            $item      = $existente[$dia] ?? null;
            $intervalo = $item?->intervalo();

            $this->grade[$dia] = [
                'hora_inicio'      => $hora($item?->hora_inicio, '09:00'),
                'hora_fim'         => $hora($item?->hora_fim, '18:00'),
                'ativo'            => $item?->ativo ?? ($dia >= 1 && $dia <= 5),
                'tem_intervalo'    => $intervalo !== null,
                'intervalo_inicio' => $intervalo[0] ?? '12:00',
                'intervalo_fim'    => $intervalo[1] ?? '13:00',
            ];
        }

        $this->modalGrade = true;
    }

    public function fecharModalGrade(): void
    {
        $this->modalGrade         = false;
        $this->gradeEditandoId    = null;
        $this->gradeEditandoNome  = '';
        $this->grade              = [];
        $this->resetErrorBag();
    }

    /** Copia horário e intervalo de um dia para todos os outros dias ativos. */
    public function copiarGradeParaTodos(int $origem): void
    {
        if (! isset($this->grade[$origem])) {
            return;
        }

        $campos = ['hora_inicio', 'hora_fim', 'tem_intervalo', 'intervalo_inicio', 'intervalo_fim'];
        foreach ($this->grade as $dia => $item) {
            if ($dia !== $origem && $item['ativo']) {
                foreach ($campos as $campo) {
                    $this->grade[$dia][$campo] = $this->grade[$origem][$campo];
                }
            }
        }
        $this->resetErrorBag();
    }

    public function salvarGrade(): void
    {
        $diasNomes = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        $regras    = [];
        $atributos = [];

        foreach ($this->grade as $dia => $item) {
            if (! $item['ativo']) {
                continue;
            }
            $regras["grade.{$dia}.hora_inicio"] = 'required|date_format:H:i';
            $regras["grade.{$dia}.hora_fim"]    = "required|date_format:H:i|after:grade.{$dia}.hora_inicio";
            $atributos["grade.{$dia}.hora_inicio"] = "início ({$diasNomes[$dia]})";
            $atributos["grade.{$dia}.hora_fim"]    = "fim ({$diasNomes[$dia]})";

            if ($item['tem_intervalo']) {
                $regras["grade.{$dia}.intervalo_inicio"] = "required|date_format:H:i|after:grade.{$dia}.hora_inicio";
                $regras["grade.{$dia}.intervalo_fim"]    = "required|date_format:H:i|after:grade.{$dia}.intervalo_inicio|before:grade.{$dia}.hora_fim";
                $atributos["grade.{$dia}.intervalo_inicio"] = "início do intervalo ({$diasNomes[$dia]})";
                $atributos["grade.{$dia}.intervalo_fim"]    = "fim do intervalo ({$diasNomes[$dia]})";
            }
        }

        $this->validate($regras, [
            'required'    => 'Informe o :attribute.',
            'date_format' => 'Horário inválido em :attribute.',
            'after'       => 'O :attribute deve ser depois de :date.',
            'before'      => 'O :attribute deve ser antes de :date.',
        ], $atributos);

        try {
            DB::transaction(function (): void {
                foreach ($this->grade as $dia => $item) {
                    $comIntervalo = $item['ativo'] && $item['tem_intervalo'];
                    GradeHorario::updateOrCreate(
                        ['profissional_id' => $this->gradeEditandoId, 'dia_semana' => $dia],
                        [
                            'hora_inicio'      => $item['hora_inicio'],
                            'hora_fim'         => $item['hora_fim'],
                            'intervalo_inicio' => $comIntervalo ? $item['intervalo_inicio'] : null,
                            'intervalo_fim'    => $comIntervalo ? $item['intervalo_fim'] : null,
                            'ativo'            => $item['ativo'],
                        ],
                    );
                }
            });
            $this->flashSucesso = "Grade de {$this->gradeEditandoNome} salva.";
            $this->fecharModalGrade();
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao salvar grade: ' . $e->getMessage();
        }
    }

    // ─── Procedimento: modais ─────────────────────────────────────
    public function abrirModalNovoProcedimento(): void
    {
        $this->resetProcedimentoForm();
        $this->procedimentoEditandoId = null;
        $this->modalProcedimento      = true;
    }

    public function abrirModalEditarProcedimento(int $id): void
    {
        $p = Procedimento::findOrFail($id);
        $this->procedimentoEditandoId = $id;
        $this->procNome     = $p->nome;
        $this->procDescricao = $p->descricao ?? '';
        $this->procDuracao  = (string) $p->duracao_minutos;
        $this->procValor    = (string) $p->valor;
        $this->procAtivo    = $p->ativo;
        $this->modalProcedimento = true;
    }

    public function fecharModalProcedimento(): void
    {
        $this->modalProcedimento = false;
        $this->resetProcedimentoForm();
    }

    public function salvarProcedimento(): void
    {
        $this->validate([
            'procNome'    => 'required|string|max:150',
            'procDuracao' => 'required|integer|min:15|max:480',
            'procValor'   => 'required|numeric|min:0',
        ]);

        try {
            $data = [
                'nome'            => $this->procNome,
                'descricao'       => $this->procDescricao ?: null,
                'duracao_minutos' => (int) $this->procDuracao,
                'valor'           => (float) str_replace(',', '.', $this->procValor),
                'ativo'           => $this->procAtivo,
            ];

            if ($this->procedimentoEditandoId) {
                Procedimento::findOrFail($this->procedimentoEditandoId)->update($data);
                $this->flashSucesso = 'Procedimento atualizado.';
            } else {
                Procedimento::create($data);
                $this->flashSucesso = 'Procedimento criado.';
            }
            $this->modalProcedimento = false;
            $this->resetProcedimentoForm();
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao salvar procedimento: ' . $e->getMessage();
        }
    }

    private function resetProcedimentoForm(): void
    {
        $this->procNome      = '';
        $this->procDescricao = '';
        $this->procDuracao   = '60';
        $this->procValor     = '';
        $this->procAtivo     = true;
        $this->resetErrorBag();
    }

    // ─── Bloqueio: modais ─────────────────────────────────────────
    public function abrirModalNovoBloqueio(): void
    {
        $this->resetBloqueioForm();
        $this->modalBloqueio = true;
    }

    public function fecharModalBloqueio(): void
    {
        $this->modalBloqueio = false;
        $this->resetBloqueioForm();
    }

    public function salvarBloqueio(BloqueioAgendaService $service): void
    {
        $regras = [
            'bloqProfissionalId' => ['required', function (string $attr, mixed $valor, \Closure $falha): void {
                if ($valor !== self::BLOQUEIO_TODOS && ! Profissional::whereKey($valor)->exists()) {
                    $falha('Selecione um profissional válido.');
                }
            }],
            'bloqDataInicio' => 'required|date_format:Y-m-d',
            'bloqMotivo'     => 'nullable|string|max:500',
        ];
        $regras += $this->bloqDiaInteiro
            ? ['bloqDataFim' => 'required|date_format:Y-m-d|after_or_equal:bloqDataInicio']
            : ['bloqHoraInicio' => 'required|date_format:H:i', 'bloqHoraFim' => 'required|date_format:H:i|after:bloqHoraInicio'];

        $this->validate($regras, [
            'bloqDataInicio.required'    => 'Informe a data.',
            'bloqDataFim.required'       => 'Informe a data final.',
            'bloqDataFim.after_or_equal' => 'A data final deve ser igual ou posterior à inicial.',
            'bloqHoraFim.after'          => 'O horário final deve ser depois do inicial.',
        ]);

        $profissionalIds = $this->bloqProfissionalId === self::BLOQUEIO_TODOS
            ? Profissional::where('ativo', true)->pluck('id')->all()
            : [$this->bloqProfissionalId];

        if ($profissionalIds === []) {
            $this->addError('bloqProfissionalId', 'Não há profissionais ativos para bloquear.');
            return;
        }

        [$inicio, $fim] = $this->bloqDiaInteiro
            ? [CarbonImmutable::parse($this->bloqDataInicio)->startOfDay(), CarbonImmutable::parse($this->bloqDataFim)->startOfDay()->addDay()]
            : [CarbonImmutable::parse("{$this->bloqDataInicio} {$this->bloqHoraInicio}"), CarbonImmutable::parse("{$this->bloqDataInicio} {$this->bloqHoraFim}")];

        try {
            $resultado = $service->criar($profissionalIds, $inicio, $fim, $this->bloqDiaInteiro, $this->bloqMotivo ?: null);
        } catch (Throwable $e) {
            Log::error('[AgendamentoConfiguracao] salvarBloqueio erro', ['message' => $e->getMessage()]);
            $this->flashErro = 'Erro ao salvar bloqueio: ' . $e->getMessage();
            return;
        }

        $conflitos = $resultado['conflitos'];
        $this->bloqConflitos = $conflitos->map(fn ($ag) => [
            'data'            => $ag->inicio_em->toDateString(),
            'horario'         => $ag->inicio_em->format('d/m H:i'),
            'paciente'        => $ag->paciente?->nome ?? '—',
            'profissional'    => $ag->profissional?->nome ?? '—',
            'profissional_id' => (string) $ag->profissional_id,
        ])->all();
        $this->bloqConflitosResumo = BloqueioAgendaService::descreverPeriodo($inicio, $fim, $this->bloqDiaInteiro);

        $this->flashSucesso = $conflitos->isEmpty()
            ? 'Bloqueio criado.'
            : 'Bloqueio criado. Há agendamentos no período — veja a lista.';
        $this->modalBloqueio = false;
        $this->resetBloqueioForm();
    }

    public function removerBloqueio(int $id, BloqueioAgendaService $service): void
    {
        try {
            $service->remover(BloqueioAgenda::findOrFail($id));
            $this->flashSucesso = 'Bloqueio removido.';
        } catch (Throwable $e) {
            Log::error('[AgendamentoConfiguracao] removerBloqueio erro', ['id' => $id, 'message' => $e->getMessage()]);
            $this->flashErro = 'Erro ao remover bloqueio: ' . $e->getMessage();
        }
    }

    public function fecharConflitos(): void
    {
        $this->bloqConflitos       = [];
        $this->bloqConflitosResumo = '';
    }

    private function resetBloqueioForm(): void
    {
        $this->bloqProfissionalId = self::BLOQUEIO_TODOS;
        $this->bloqDiaInteiro     = true;
        $this->bloqDataInicio     = now()->toDateString();
        $this->bloqDataFim        = now()->toDateString();
        $this->bloqHoraInicio     = '08:00';
        $this->bloqHoraFim        = '12:00';
        $this->bloqMotivo         = '';
        $this->resetErrorBag();
    }

    public function updatedBloqDataInicio(): void
    {
        if ($this->bloqDataFim < $this->bloqDataInicio) {
            $this->bloqDataFim = $this->bloqDataInicio;
        }
    }

    public function render(): View
    {
        $bloqueioService = app(BloqueioAgendaService::class);
        $profissionais = Profissional::orderBy('nome')->get(['id', 'nome', 'email', 'telefone', 'cor_agenda', 'ativo']);
        $procedimentos = Procedimento::orderBy('nome')->get(['id', 'nome', 'duracao_minutos', 'valor', 'ativo']);

        $bloqueios = $this->aba === 'bloqueios'
            ? $bloqueioService->agrupar(
                BloqueioAgenda::with('profissional:id,nome,cor_agenda')
                    ->select(['id', 'profissional_id', 'grupo_id', 'inicio_em', 'fim_em', 'dia_inteiro', 'motivo'])
                    ->where('fim_em', '>', now())
                    ->orderBy('inicio_em')
                    ->limit(300)
                    ->get(),
            )
            : collect();

        return view('livewire.agendamento-configuracao-index', compact('profissionais', 'procedimentos', 'bloqueios'))
            ->layout('layouts.app', ['title' => 'Configuração — Agenda']);
    }
}
