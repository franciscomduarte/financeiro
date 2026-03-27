<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FaseTransacao;
use App\Enums\FormaPagamento;
use App\Enums\RecorrenciaTransacao;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\Transacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transacao>
 */
class TransacaoFactory extends Factory
{
    protected $model = Transacao::class;

    public function definition(): array
    {
        $tipo       = $this->faker->randomElement(TipoTransacao::cases());
        $valorBruto = $this->faker->randomFloat(2, 50, 5000);
        $taxa       = 0.00;
        $imposto    = $tipo === TipoTransacao::Entrada ? round($valorBruto * 0.06, 2) : 0.00;

        return [
            'tipo'             => $tipo,
            'fase'             => $this->faker->randomElement(FaseTransacao::cases()),
            'categoria'        => $this->faker->randomElement([
                'Procedimento Facial', 'Depilação', 'Infraestrutura', 'Utilidades',
                'Marketing', 'Burocracia', 'Impostos', 'Pessoal', 'Insumos',
            ]),
            'descricao'        => $this->faker->sentence(4),
            'valor_bruto'      => $valorBruto,
            'taxa_operacional' => $taxa,
            'imposto_estimado' => $imposto,
            'valor_liquido'    => round($valorBruto - $taxa - $imposto, 2),
            'data_competencia' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'forma_pagamento'  => FormaPagamento::Pix,
            'num_parcelas'     => 1,
            'parcela_atual'    => 1,
            'status'           => $this->faker->randomElement(StatusTransacao::cases()),
            'recorrencia'      => RecorrenciaTransacao::Unica,
        ];
    }
}
