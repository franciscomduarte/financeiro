<?php

declare(strict_types=1);

namespace App\Actions\Comissoes;

use App\Models\Profissional;
use InvalidArgumentException;

/** Percentual de comissão do profissional (vale para os meses ainda não fechados). */
class DefinirPercentualComissaoAction
{
    public function execute(string $profissionalId, float $percentual): Profissional
    {
        if ($percentual < 0 || $percentual > 100) {
            throw new InvalidArgumentException('O percentual vai de 0 a 100.');
        }

        $profissional = Profissional::query()->findOrFail($profissionalId);
        $profissional->update(['comissao_percentual' => round($percentual, 2)]);

        return $profissional;
    }
}
