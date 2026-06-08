<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\GradeHorario;
use App\Models\Procedimento;
use App\Models\Profissional;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
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
    /** @var array<int, array{hora_inicio: string, hora_fim: string, ativo: bool}> */
    public array $grade = [];

    // ─── Modal: Procedimento ──────────────────────────────────────
    public bool    $modalProcedimento      = false;
    public ?int    $procedimentoEditandoId = null;
    public string  $procNome      = '';
    public string  $procDescricao = '';
    public string  $procDuracao   = '60';
    public string  $procValor     = '';
    public bool    $procAtivo     = true;

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
        for ($dia = 0; $dia <= 6; $dia++) {
            $this->grade[$dia] = [
                'hora_inicio' => $existente[$dia]?->hora_inicio ?? '09:00',
                'hora_fim'    => $existente[$dia]?->hora_fim    ?? '18:00',
                'ativo'       => $existente[$dia]?->ativo       ?? ($dia >= 1 && $dia <= 5),
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
    }

    public function salvarGrade(): void
    {
        try {
            DB::transaction(function (): void {
                foreach ($this->grade as $dia => $item) {
                    GradeHorario::updateOrCreate(
                        ['profissional_id' => $this->gradeEditandoId, 'dia_semana' => $dia],
                        ['hora_inicio' => $item['hora_inicio'], 'hora_fim' => $item['hora_fim'], 'ativo' => $item['ativo']],
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

    public function render(): View
    {
        $profissionais = Profissional::orderBy('nome')->get(['id', 'nome', 'email', 'telefone', 'cor_agenda', 'ativo']);
        $procedimentos = Procedimento::orderBy('nome')->get(['id', 'nome', 'duracao_minutos', 'valor', 'ativo']);

        return view('livewire.agendamento-configuracao-index', compact('profissionais', 'procedimentos'))
            ->layout('layouts.app', ['title' => 'Configuração — Agenda']);
    }
}
