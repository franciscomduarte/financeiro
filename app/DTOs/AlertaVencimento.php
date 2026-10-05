<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TipoAlertaVencimento;
use Carbon\CarbonImmutable;

/** Um item que vence (ou venceu) e precisa de atenção. */
final readonly class AlertaVencimento
{
    public function __construct(
        public TipoAlertaVencimento $tipo,
        public string $titulo,
        public CarbonImmutable $data,
        public ?float $valor = null,
    ) {}

    public function diasPara(CarbonImmutable $hoje): int
    {
        return (int) $hoje->startOfDay()->diffInDays($this->data->startOfDay(), false);
    }

    /** "vence hoje", "vence amanhã", "vence em 5 dias", "venceu há 2 dias" (contrato: "termina…"). */
    public function prazo(CarbonImmutable $hoje): string
    {
        $dias = $this->diasPara($hoje);
        // Contrato não "vence": termina
        [$futuro, $passado] = $this->tipo === TipoAlertaVencimento::Contrato ? ['termina', 'terminou'] : ['vence', 'venceu'];

        return match (true) {
            $dias === 0  => "{$futuro} hoje",
            $dias === 1  => "{$futuro} amanhã",
            $dias > 1    => "{$futuro} em {$dias} dias",
            $dias === -1 => "{$passado} ontem",
            default      => "{$passado} há " . abs($dias) . ' dias',
        };
    }

    public function valorFormatado(): ?string
    {
        return $this->valor === null ? null : 'R$ ' . number_format($this->valor, 2, ',', '.');
    }
}
