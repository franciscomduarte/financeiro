<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StatusFornecedor;
use App\Models\Fornecedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fornecedor>
 */
class FornecedorFactory extends Factory
{
    protected $model = Fornecedor::class;

    public function definition(): array
    {
        return [
            'nome_fantasia'               => fake()->company(),
            'razao_social'                => fake()->optional()->company() . ' Ltda',
            'cnpj'                        => fake()->optional()->numerify('##.###.###/####-##'),
            'servico_prestado'            => fake()->sentence(4),
            'categoria'                   => fake()->optional()->randomElement(['TI', 'Limpeza', 'Segurança', 'Alimentação', 'Saúde']),
            'contato_nome'                => fake()->optional()->name(),
            'contato_telefone'            => fake()->optional()->phoneNumber(),
            'contato_email'               => fake()->optional()->safeEmail(),
            'contato_emergencia_nome'     => null,
            'contato_emergencia_telefone' => null,
            'status'                      => StatusFornecedor::Ativo->value,
            'observacoes'                 => null,
        ];
    }

    public function ativo(): static
    {
        return $this->state(['status' => StatusFornecedor::Ativo->value]);
    }

    public function suspenso(): static
    {
        return $this->state(['status' => StatusFornecedor::Suspenso->value]);
    }

    public function encerrado(): static
    {
        return $this->state(['status' => StatusFornecedor::Encerrado->value]);
    }
}
