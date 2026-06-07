<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StockBatchStatusEnum;
use App\Enums\StockMovementTypeEnum;
use App\Models\StockBatch;
use App\Models\StockCategory;
use App\Models\StockMovement;
use App\Models\StockProduct;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Categorias ──────────────────────────────────────────
        $cats = [
            ['name' => 'Toxina Botulínica',      'color' => '#7c3aed'],
            ['name' => 'Preenchedor',             'color' => '#db2777'],
            ['name' => 'Bioestimulador',          'color' => '#ea580c'],
            ['name' => 'Fio de PDO',              'color' => '#0891b2'],
            ['name' => 'Skinbooster',             'color' => '#059669'],
            ['name' => 'Cosmético Profissional',  'color' => '#d97706'],
            ['name' => 'Insumo de Procedimento',  'color' => '#64748b'],
        ];

        $categories = [];
        foreach ($cats as $cat) {
            $categories[$cat['name']] = StockCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }

        // ─── Produtos ─────────────────────────────────────────────
        $products = [
            [
                'name'                   => 'Toxina Botulínica (Botox) 100UI',
                'category'               => 'Toxina Botulínica',
                'unit_type'              => 'UI',
                'quantity_per_package'   => 100,
                'unit_cost'              => 8.50,
                'minimum_stock_quantity' => 200,
                'beyond_use_hours'       => 4,
                'requires_lot_control'   => true,
            ],
            [
                'name'                   => 'Toxina Botulínica (Dysport) 300UI',
                'category'               => 'Toxina Botulínica',
                'unit_type'              => 'UI',
                'quantity_per_package'   => 300,
                'unit_cost'              => 3.20,
                'minimum_stock_quantity' => 300,
                'beyond_use_hours'       => 4,
                'requires_lot_control'   => true,
            ],
            [
                'name'                   => 'Ácido Hialurônico 1ml',
                'category'               => 'Preenchedor',
                'unit_type'              => 'ml',
                'quantity_per_package'   => 1,
                'unit_cost'              => 180.00,
                'minimum_stock_quantity' => 3,
                'beyond_use_hours'       => null,
                'requires_lot_control'   => true,
            ],
            [
                'name'                   => 'Sculptra 150mg',
                'category'               => 'Bioestimulador',
                'unit_type'              => 'mg',
                'quantity_per_package'   => 150,
                'unit_cost'              => 4.00,
                'minimum_stock_quantity' => 150,
                'beyond_use_hours'       => 72,
                'requires_lot_control'   => true,
            ],
            [
                'name'                   => 'Fio de PDO 29G',
                'category'               => 'Fio de PDO',
                'unit_type'              => 'unidade',
                'quantity_per_package'   => 10,
                'unit_cost'              => 12.00,
                'minimum_stock_quantity' => 20,
                'beyond_use_hours'       => null,
                'requires_lot_control'   => false,
            ],
            [
                'name'                   => 'Luva Descartável (par)',
                'category'               => 'Insumo de Procedimento',
                'unit_type'              => 'unidade',
                'quantity_per_package'   => 100,
                'unit_cost'              => 0.25,
                'minimum_stock_quantity' => 50,
                'beyond_use_hours'       => null,
                'requires_lot_control'   => false,
            ],
            [
                'name'                   => 'Seringa 1ml',
                'category'               => 'Insumo de Procedimento',
                'unit_type'              => 'unidade',
                'quantity_per_package'   => 100,
                'unit_cost'              => 0.80,
                'minimum_stock_quantity' => 50,
                'beyond_use_hours'       => null,
                'requires_lot_control'   => false,
            ],
        ];

        $productModels = [];
        foreach ($products as $p) {
            $categoryId = $categories[$p['category']]->id;
            $product    = StockProduct::firstOrCreate(
                ['name' => $p['name']],
                [
                    'category_id'            => $categoryId,
                    'unit_type'              => $p['unit_type'],
                    'quantity_per_package'   => $p['quantity_per_package'],
                    'unit_cost'              => $p['unit_cost'],
                    'minimum_stock_quantity' => $p['minimum_stock_quantity'],
                    'beyond_use_hours'       => $p['beyond_use_hours'],
                    'requires_lot_control'   => $p['requires_lot_control'],
                    'active'                 => true,
                ]
            );
            $productModels[$p['name']] = $product;
        }

        // ─── Lotes para produtos injetáveis ───────────────────────
        $injectables = [
            'Toxina Botulínica (Botox) 100UI'  => ['lots' => 2, 'qty' => 100.0, 'cost' => 850.00],
            'Toxina Botulínica (Dysport) 300UI' => ['lots' => 2, 'qty' => 300.0, 'cost' => 960.00],
            'Ácido Hialurônico 1ml'             => ['lots' => 3, 'qty' => 1.0,   'cost' => 180.00],
            'Sculptra 150mg'                    => ['lots' => 1, 'qty' => 150.0, 'cost' => 600.00],
        ];

        foreach ($injectables as $productName => $config) {
            $product = $productModels[$productName];

            if ($product->batches()->exists()) {
                continue;
            }

            for ($i = 1; $i <= $config['lots']; $i++) {
                $isFirst   = $i === 1;
                $openedAt  = $isFirst ? Carbon::now()->subHours(1) : null;
                $beyondExp = null;

                if ($isFirst && $product->beyond_use_hours) {
                    $beyondExp = $openedAt->copy()->addHours($product->beyond_use_hours);
                }

                $available = $isFirst ? $config['qty'] * 0.6 : $config['qty'];

                $batch = StockBatch::create([
                    'product_id'            => $product->id,
                    'lot_number'            => 'LOT' . strtoupper(substr(md5($productName . $i), 0, 6)),
                    'expires_at'            => Carbon::now()->addMonths(18)->toDateString(),
                    'quantity_total'        => $config['qty'],
                    'quantity_available'    => $available,
                    'purchase_cost'         => $config['cost'],
                    'purchased_at'          => Carbon::now()->subDays(10)->toDateString(),
                    'opened_at'             => $openedAt,
                    'beyond_use_expires_at' => $beyondExp,
                    'status'                => $isFirst ? StockBatchStatusEnum::Open->value : StockBatchStatusEnum::Sealed->value,
                ]);

                StockMovement::create([
                    'batch_id'          => $batch->id,
                    'product_id'        => $product->id,
                    'type'              => StockMovementTypeEnum::Purchase->value,
                    'quantity'          => $config['qty'],
                    'unit_cost_at_time' => round($config['cost'] / $config['qty'], 4),
                    'created_at'        => Carbon::now()->subDays(10),
                ]);
            }
        }

        // ─── Lotes para insumos (sem controle de lote individual) ─
        $consumables = [
            'Fio de PDO 29G'         => ['qty' => 50.0,  'cost' => 60.00],
            'Luva Descartável (par)' => ['qty' => 200.0, 'cost' => 50.00],
            'Seringa 1ml'            => ['qty' => 300.0, 'cost' => 240.00],
        ];

        foreach ($consumables as $productName => $config) {
            $product = $productModels[$productName];

            if ($product->batches()->exists()) {
                continue;
            }

            $batch = StockBatch::create([
                'product_id'         => $product->id,
                'lot_number'         => null,
                'expires_at'         => Carbon::now()->addYears(2)->toDateString(),
                'quantity_total'     => $config['qty'],
                'quantity_available' => $config['qty'],
                'purchase_cost'      => $config['cost'],
                'purchased_at'       => Carbon::now()->subDays(5)->toDateString(),
                'status'             => StockBatchStatusEnum::Open->value,
                'opened_at'          => Carbon::now()->subDays(5),
            ]);

            StockMovement::create([
                'batch_id'          => $batch->id,
                'product_id'        => $product->id,
                'type'              => StockMovementTypeEnum::Purchase->value,
                'quantity'          => $config['qty'],
                'unit_cost_at_time' => round($config['cost'] / $config['qty'], 4),
                'created_at'        => Carbon::now()->subDays(5),
            ]);
        }

        $this->command?->info('StockSeeder: categorias, produtos e lotes criados com sucesso.');
    }
}
