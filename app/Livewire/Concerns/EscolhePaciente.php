<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\StatusPaciente;
use App\Models\Paciente;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/** Campo de busca de paciente nos formulários (digita o nome, escolhe da lista). */
trait EscolhePaciente
{
    public string $buscaPaciente = '';
    public string $pacienteId = '';
    public string $pacienteNome = '';

    #[Computed]
    public function pacientesEncontrados(): Collection
    {
        $termo = trim($this->buscaPaciente);
        if (mb_strlen($termo) < 2 || $this->pacienteNome === $termo) {
            return collect();
        }

        return Paciente::query()
            ->select(['id', 'nome', 'telefone'])
            ->where('status', StatusPaciente::Ativo)
            ->whereNull('anonimizado_em')
            ->where('nome', 'ilike', '%' . str_replace(['%', '_'], ['\%', '\_'], $termo) . '%')
            ->orderBy('nome')
            ->limit(8)
            ->get();
    }

    public function escolherPaciente(string $id): void
    {
        $paciente = Paciente::query()->select(['id', 'nome'])->findOrFail($id);

        $this->pacienteId    = $paciente->id;
        $this->pacienteNome  = $paciente->nome;
        $this->buscaPaciente = $paciente->nome;
        $this->resetErrorBag('pacienteId');
    }

    public function updatedBuscaPaciente(): void
    {
        if ($this->buscaPaciente !== $this->pacienteNome) {
            $this->pacienteId = $this->pacienteNome = '';
        }
    }
}
