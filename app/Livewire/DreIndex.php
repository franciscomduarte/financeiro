<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\DreService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/** DRE gerencial do mês (por competência), comparada com o mês anterior, e a evolução de 12 meses. */
class DreIndex extends Component
{
    #[Url]
    public string $mes = '';

    public function mount(): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->mes)) {
            $this->mes = today()->format('Y-m');
        }
    }

    public function navegar(int $delta): void
    {
        $this->mes = $this->mesRef()->addMonths($delta)->format('Y-m');
    }

    private function mesRef(): CarbonImmutable
    {
        return preg_match('/^\d{4}-\d{2}$/', $this->mes) ? CarbonImmutable::createFromFormat('!Y-m', $this->mes) : CarbonImmutable::today()->startOfMonth();
    }

    public function render(DreService $dre): View
    {
        $mes = $this->mesRef();

        return view('livewire.dre-index', [
            'dre'      => $dre->mes($mes),
            'anterior' => $dre->mes($mes->subMonth()),
            'serie'    => $dre->serie($mes, 12),
            'mesRef'   => $mes,
        ])->layout('layouts.app', ['title' => 'DRE']);
    }
}
