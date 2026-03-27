<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TipoAnexo;
use App\Models\Transacao;
use App\Models\TransacaoAnexo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransacaoAnexo>
 */
class TransacaoAnexoFactory extends Factory
{
    protected $model = TransacaoAnexo::class;

    public function definition(): array
    {
        $tipo     = $this->faker->randomElement(TipoAnexo::cases());
        $ext      = $this->faker->randomElement(['pdf', 'jpg', 'png']);
        $nome     = "{$tipo->value}_{$this->faker->uuid()}.{$ext}";
        $mimeMap  = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'];

        return [
            'transacao_id' => Transacao::factory(),
            'tipo'         => $tipo,
            'nome_arquivo' => $nome,
            'caminho'      => "anexos/transacoes/fake/{$nome}",
            'mime_type'    => $mimeMap[$ext],
            'tamanho_bytes' => $this->faker->numberBetween(10000, 500000),
        ];
    }
}
