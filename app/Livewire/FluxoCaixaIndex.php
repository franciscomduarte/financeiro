<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\FluxoCaixaService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Fluxo de caixa: projeção (saldo de hoje + o que está a receber/pagar + recorrências) e o
 * realizado (o que entrou e saiu de fato), por mês no ano ou por dia no mês.
 */
class FluxoCaixaIndex extends Component
{
    public const HORIZONTES = [30, 60, 90];

    #[Url(except: 'projetado')]
    public string $aba = 'projetado';

    #[Url(except: 90)]
    public int $dias = 90;

    /** Realizado: "2026" (meses do ano) ou "2026-10" (dias do mês). */
    #[Url]
    public string $periodo = '';

    public function mount(): void
    {
        $this->aba  = in_array($this->aba, ['projetado', 'realizado'], true) ? $this->aba : 'projetado';
        $this->dias = in_array($this->dias, self::HORIZONTES, true) ? $this->dias : 90;
        if (! preg_match('/^\d{4}(-\d{2})?$/', $this->periodo)) {
            $this->periodo = today()->format('Y');
        }
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string} início, fim e agrupamento */
    private function intervalo(): array
    {
        if (preg_match('/^\d{4}-\d{2}$/', $this->periodo)) {
            $m = CarbonImmutable::createFromFormat('!Y-m', $this->periodo);

            return [$m, $m->endOfMonth(), 'dia'];
        }
        $ano = preg_match('/^\d{4}$/', $this->periodo) ? (int) $this->periodo : (int) today()->format('Y');

        return [CarbonImmutable::create($ano, 1, 1), CarbonImmutable::create($ano, 12, 31), 'mes'];
    }

    public function render(FluxoCaixaService $fluxo): View
    {
        [$inicio, $fim, $por] = $this->intervalo();

        return view('livewire.fluxo-caixa-index', [
            'projecao'  => $this->aba === 'projetado' ? $fluxo->projetado($this->dias) : null,
            'realizado' => $this->aba === 'realizado' ? $fluxo->realizado($inicio, $fim, $por) : null,
            'inicio'    => $inicio,
            'por'       => $por,
            'anos'      => range((int) today()->format('Y'), (int) today()->format('Y') - 4),
        ])->layout('layouts.app', ['title' => 'Fluxo de caixa']);
    }
}
