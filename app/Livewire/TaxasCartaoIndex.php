<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\TaxaCartao;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class TaxasCartaoIndex extends Component
{
    /** @var array<string, array{id: string, percentual: string, ativo: bool}> */
    public array $taxas = [];

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        $this->carregarTaxas();
    }

    private function carregarTaxas(): void
    {
        $this->taxas = TaxaCartao::orderBy('modalidade')
            ->select(['id', 'modalidade', 'percentual', 'ativo'])
            ->get()
            ->keyBy('id')
            ->map(fn (TaxaCartao $t) => [
                'id'         => $t->id,
                'modalidade' => $t->modalidade,
                'percentual' => (string) $t->percentual,
                'ativo'      => $t->ativo,
            ])
            ->toArray();
    }

    public function salvarTaxa(string $id): void
    {
        $this->validate([
            "taxas.{$id}.percentual" => 'required|numeric|min:0|max:100',
        ], [
            "taxas.{$id}.percentual.required" => 'Percentual obrigatório.',
            "taxas.{$id}.percentual.numeric"  => 'Percentual deve ser numérico.',
            "taxas.{$id}.percentual.min"      => 'Percentual deve ser ≥ 0.',
            "taxas.{$id}.percentual.max"      => 'Percentual deve ser ≤ 100.',
        ]);

        try {
            $taxa = TaxaCartao::findOrFail($id);
            $taxa->update([
                'percentual' => (float) $this->taxas[$id]['percentual'],
                'ativo'      => $this->taxas[$id]['ativo'],
            ]);

            $this->flashSucesso = "Taxa de {$taxa->modalidade} salva com sucesso.";
        } catch (Throwable $e) {
            Log::error('Erro ao salvar taxa de cartão', ['id' => $id, 'error' => $e->getMessage()]);
            $this->flashErro = 'Erro ao salvar taxa.';
        }
    }

    public function salvarTodas(): void
    {
        $rules = [];
        foreach (array_keys($this->taxas) as $id) {
            $rules["taxas.{$id}.percentual"] = 'required|numeric|min:0|max:100';
        }

        $this->validate($rules);

        try {
            DB::transaction(function (): void {
                app(\App\Support\ClinicaAtual::class)->garantirEscrita(); // update em massa não dispara eventos do Model
                foreach ($this->taxas as $id => $dados) {
                    TaxaCartao::where('id', $id)->update([
                        'percentual' => (float) $dados['percentual'],
                        'ativo'      => $dados['ativo'],
                    ]);
                }
            });

            $this->flashSucesso = 'Todas as taxas foram salvas com sucesso.';
        } catch (Throwable $e) {
            Log::error('Erro ao salvar todas as taxas', ['error' => $e->getMessage()]);
            $this->flashErro = 'Erro ao salvar taxas.';
        }
    }

    public function toggleAtivo(string $id): void
    {
        $this->taxas[$id]['ativo'] = ! $this->taxas[$id]['ativo'];
    }

    public function render(): View
    {
        return view('livewire.taxas-cartao-index')
            ->layout('layouts.app', ['title' => 'Taxas de Cartão']);
    }
}
