<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaxasCartaoSeeder extends Seeder
{
    public function run(): void
    {
        $taxas = [
            ['modalidade' => 'pix',         'percentual' => 0.00],
            ['modalidade' => 'dinheiro',     'percentual' => 0.00],
            ['modalidade' => 'debito',       'percentual' => 1.50],
            ['modalidade' => 'credito_1x',   'percentual' => 2.50],
            ['modalidade' => 'credito_2x',   'percentual' => 3.00],
            ['modalidade' => 'credito_3x',   'percentual' => 3.50],
            ['modalidade' => 'credito_4x',   'percentual' => 4.00],
            ['modalidade' => 'credito_5x',   'percentual' => 4.50],
            ['modalidade' => 'credito_6x',   'percentual' => 5.00],
            ['modalidade' => 'credito_7x',   'percentual' => 5.50],
            ['modalidade' => 'credito_8x',   'percentual' => 6.00],
            ['modalidade' => 'credito_9x',   'percentual' => 6.50],
            ['modalidade' => 'credito_10x',  'percentual' => 7.00],
            ['modalidade' => 'credito_11x',  'percentual' => 7.50],
            ['modalidade' => 'credito_12x',  'percentual' => 8.00],
        ];

        foreach ($taxas as $taxa) {
            DB::table('taxas_cartao')->upsert(
                [
                    'id'         => Str::uuid()->toString(),
                    'modalidade' => $taxa['modalidade'],
                    'percentual' => $taxa['percentual'],
                    'ativo'      => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                ['modalidade'],
                ['percentual', 'ativo', 'updated_at'],
            );
        }
    }
}
