<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RiscoContrato;
use App\Enums\StatusContrato;
use App\Models\Contrato;
use App\Models\Fornecedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contrato>
 */
class ContratoFactory extends Factory
{
    protected $model = Contrato::class;

    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-2 years', 'now');
        $fim    = fake()->optional()->dateTimeBetween($inicio, '+2 years');

        return [
            'fornecedor_id'             => Fornecedor::factory(),
            'valor_mensal'              => fake()->randomFloat(2, 100, 10000),
            'data_inicio'               => $inicio->format('Y-m-d'),
            'data_fim'                  => $fim?->format('Y-m-d'),
            'periodicidade_reajuste'    => 'anual',
            'data_proximo_reajuste'     => null,
            'indice_reajuste'           => null,
            'multa_rescisao_valor'      => null,
            'multa_rescisao_percentual' => null,
            'aviso_previo_dias'         => 30,
            'arquivo_contrato_path'     => null,
            'arquivo_contrato_nome'     => null,
            'link_contrato'             => null,
            'risco'                     => RiscoContrato::Baixo->value,
            'status'                    => StatusContrato::Ativo->value,
            'observacoes'               => null,
        ];
    }

    public function ativo(): static
    {
        return $this->state(['status' => StatusContrato::Ativo->value]);
    }

    public function encerrado(): static
    {
        return $this->state(['status' => StatusContrato::Encerrado->value]);
    }

    public function comVencimentoProximo(): static
    {
        return $this->state([
            'data_fim' => now()->addDays(30)->toDateString(),
            'status'   => StatusContrato::Ativo->value,
        ]);
    }
}
