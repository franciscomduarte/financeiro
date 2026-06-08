<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // ─── Catálogo de categorias ───────────────────────────────────
    private function categorias(): array
    {
        return [
            ['name' => 'Toxina Botulínica',      'color' => '#7c3aed'],
            ['name' => 'Preenchedor',             'color' => '#db2777'],
            ['name' => 'Bioestimulador',          'color' => '#ea580c'],
            ['name' => 'Fio de PDO',              'color' => '#0891b2'],
            ['name' => 'Skinbooster',             'color' => '#059669'],
            ['name' => 'Cosmético Profissional',  'color' => '#d97706'],
            ['name' => 'Insumo de Procedimento',  'color' => '#64748b'],
            ['name' => 'Peeling & Ácidos',        'color' => '#0d9488'],
            ['name' => 'Anestésico',              'color' => '#dc2626'],
        ];
    }

    // ─── Catálogo de produtos ─────────────────────────────────────
    // Campos: cat, name, unit, qty, cost, min, buh (beyond_use_hours), lot
    private function produtos(): array
    {
        return [
            // ── Toxina Botulínica ──────────────────────────────────
            ['cat' => 'Toxina Botulínica', 'name' => 'Botox (Allergan) 100UI',     'unit' => 'UI',      'qty' => 100.0,  'cost' => 8.50,    'min' => 100.0, 'buh' => 4,   'lot' => true],
            ['cat' => 'Toxina Botulínica', 'name' => 'Botox (Allergan) 50UI',      'unit' => 'UI',      'qty' => 50.0,   'cost' => 9.00,    'min' => 50.0,  'buh' => 4,   'lot' => true],
            ['cat' => 'Toxina Botulínica', 'name' => 'Dysport (Ipsen) 300UI',      'unit' => 'UI',      'qty' => 300.0,  'cost' => 3.00,    'min' => 300.0, 'buh' => 4,   'lot' => true],
            ['cat' => 'Toxina Botulínica', 'name' => 'Xeomin 100UI',               'unit' => 'UI',      'qty' => 100.0,  'cost' => 8.00,    'min' => 100.0, 'buh' => 4,   'lot' => true],
            ['cat' => 'Toxina Botulínica', 'name' => 'Nabota 100UI',               'unit' => 'UI',      'qty' => 100.0,  'cost' => 7.50,    'min' => 100.0, 'buh' => 4,   'lot' => true],
            ['cat' => 'Toxina Botulínica', 'name' => 'Botulift 150UI',             'unit' => 'UI',      'qty' => 150.0,  'cost' => 5.50,    'min' => 150.0, 'buh' => 4,   'lot' => true],

            // ── Preenchedor / AH ───────────────────────────────────
            ['cat' => 'Preenchedor', 'name' => 'Juvéderm Voluma XC 1ml',           'unit' => 'ml',      'qty' => 1.0,    'cost' => 680.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Preenchedor', 'name' => 'Juvéderm Ultra 4 1ml',             'unit' => 'ml',      'qty' => 1.0,    'cost' => 590.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Preenchedor', 'name' => 'Restylane Lyft 1ml',               'unit' => 'ml',      'qty' => 1.0,    'cost' => 520.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Preenchedor', 'name' => 'Restylane Kysse 1ml',              'unit' => 'ml',      'qty' => 1.0,    'cost' => 480.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Preenchedor', 'name' => 'Belotero Balance 1ml',             'unit' => 'ml',      'qty' => 1.0,    'cost' => 420.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Preenchedor', 'name' => 'Saypha Volume 1ml',                'unit' => 'ml',      'qty' => 1.0,    'cost' => 350.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Preenchedor', 'name' => 'Princess Volume 1ml',              'unit' => 'ml',      'qty' => 1.0,    'cost' => 320.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Preenchedor', 'name' => 'Stylage M 1ml',                    'unit' => 'ml',      'qty' => 1.0,    'cost' => 380.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Preenchedor', 'name' => 'Teosyal RHA4 1ml',                 'unit' => 'ml',      'qty' => 1.0,    'cost' => 560.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],

            // ── Bioestimulador ─────────────────────────────────────
            ['cat' => 'Bioestimulador', 'name' => 'Sculptra (PLLA) 150mg',         'unit' => 'mg',      'qty' => 150.0,  'cost' => 4.00,    'min' => 150.0, 'buh' => 72,  'lot' => true],
            ['cat' => 'Bioestimulador', 'name' => 'Radiesse 1.5ml',                'unit' => 'ml',      'qty' => 1.5,    'cost' => 850.00,  'min' => 1.0,   'buh' => null, 'lot' => true],
            ['cat' => 'Bioestimulador', 'name' => 'Ellansé S 1ml',                 'unit' => 'ml',      'qty' => 1.0,    'cost' => 920.00,  'min' => 1.0,   'buh' => null, 'lot' => true],
            ['cat' => 'Bioestimulador', 'name' => 'Lanluma V 180mg',               'unit' => 'mg',      'qty' => 180.0,  'cost' => 3.80,    'min' => 180.0, 'buh' => 72,  'lot' => true],

            // ── Fio de PDO ─────────────────────────────────────────
            ['cat' => 'Fio de PDO', 'name' => 'Fio PDO Liso 29G 38mm',            'unit' => 'unidade', 'qty' => 1.0,    'cost' => 8.00,    'min' => 10.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Fio de PDO', 'name' => 'Fio PDO Liso 30G 25mm',            'unit' => 'unidade', 'qty' => 1.0,    'cost' => 6.00,    'min' => 10.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Fio de PDO', 'name' => 'Fio PDO Helicoidal 30G 38mm',      'unit' => 'unidade', 'qty' => 1.0,    'cost' => 15.00,   'min' => 10.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Fio de PDO', 'name' => 'Fio PDO Espinha de Peixe 29G',     'unit' => 'unidade', 'qty' => 1.0,    'cost' => 18.00,   'min' => 10.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Fio de PDO', 'name' => 'Fio PDO Cog 19G 100mm',            'unit' => 'unidade', 'qty' => 1.0,    'cost' => 45.00,   'min' => 5.0,   'buh' => null, 'lot' => false],
            ['cat' => 'Fio de PDO', 'name' => 'Fio PDO Cog 21G 60mm',             'unit' => 'unidade', 'qty' => 1.0,    'cost' => 35.00,   'min' => 5.0,   'buh' => null, 'lot' => false],

            // ── Skinbooster / Meso ─────────────────────────────────
            ['cat' => 'Skinbooster', 'name' => 'Profhilo 2ml',                     'unit' => 'ml',      'qty' => 2.0,    'cost' => 1200.00, 'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Skinbooster', 'name' => 'Jalupro Classic 3ml',              'unit' => 'ml',      'qty' => 3.0,    'cost' => 380.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Skinbooster', 'name' => 'Jalupro HMW 3ml',                  'unit' => 'ml',      'qty' => 3.0,    'cost' => 480.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Skinbooster', 'name' => 'NCTF 135 HA 3ml',                  'unit' => 'ml',      'qty' => 3.0,    'cost' => 220.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],
            ['cat' => 'Skinbooster', 'name' => 'Redensity I 3ml',                  'unit' => 'ml',      'qty' => 3.0,    'cost' => 350.00,  'min' => 1.0,   'buh' => 4,   'lot' => true],

            // ── Peeling & Ácidos ───────────────────────────────────
            ['cat' => 'Peeling & Ácidos', 'name' => 'Ácido Glicólico 35%',         'unit' => 'ml',      'qty' => 1.0,    'cost' => 2.50,    'min' => 30.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Peeling & Ácidos', 'name' => 'Ácido Glicólico 70%',         'unit' => 'ml',      'qty' => 1.0,    'cost' => 3.50,    'min' => 30.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Peeling & Ácidos', 'name' => 'Ácido Salicílico 20%',        'unit' => 'ml',      'qty' => 1.0,    'cost' => 3.00,    'min' => 30.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Peeling & Ácidos', 'name' => 'TCA 30%',                     'unit' => 'ml',      'qty' => 1.0,    'cost' => 4.50,    'min' => 20.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Peeling & Ácidos', 'name' => 'Ácido Mandélico 40%',         'unit' => 'ml',      'qty' => 1.0,    'cost' => 3.80,    'min' => 30.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Peeling & Ácidos', 'name' => 'Ácido Retinoico 1%',          'unit' => 'ml',      'qty' => 1.0,    'cost' => 8.00,    'min' => 10.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Peeling & Ácidos', 'name' => 'Ácido Lático 70%',            'unit' => 'ml',      'qty' => 1.0,    'cost' => 3.20,    'min' => 30.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Peeling & Ácidos', 'name' => 'Ácido Kójico 10%',            'unit' => 'ml',      'qty' => 1.0,    'cost' => 4.00,    'min' => 20.0,  'buh' => null, 'lot' => false],

            // ── Anestésico ─────────────────────────────────────────
            ['cat' => 'Anestésico', 'name' => 'EMLA Creme 5% 30g',                 'unit' => 'g',       'qty' => 30.0,   'cost' => 1.20,    'min' => 60.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Anestésico', 'name' => 'BioNumb Creme Anestésico 30g',      'unit' => 'g',       'qty' => 30.0,   'cost' => 1.80,    'min' => 60.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Anestésico', 'name' => 'Anestop Creme 30g',                 'unit' => 'g',       'qty' => 30.0,   'cost' => 1.50,    'min' => 60.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Anestésico', 'name' => 'Lidocaína 2% s/ Vasoconstritor 20ml', 'unit' => 'ml',    'qty' => 20.0,   'cost' => 0.80,    'min' => 40.0,  'buh' => null, 'lot' => false],

            // ── Insumo de Procedimento ─────────────────────────────
            ['cat' => 'Insumo de Procedimento', 'name' => 'Luva Nitrílica P (par)',          'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.35, 'min' => 200.0, 'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Luva Nitrílica M (par)',          'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.35, 'min' => 200.0, 'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Luva Nitrílica G (par)',          'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.35, 'min' => 200.0, 'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Seringa 1ml Insulina',            'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.80, 'min' => 100.0, 'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Seringa 3ml',                     'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.40, 'min' => 100.0, 'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Seringa 5ml',                     'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.45, 'min' => 50.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Seringa 10ml',                    'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.55, 'min' => 50.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Agulha 30G 0.5" (13x0.3)',       'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.15, 'min' => 200.0, 'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Agulha 27G 0.75" (25x0.75)',     'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.18, 'min' => 100.0, 'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Cânula Flexível 23G 38mm',       'unit' => 'unidade', 'qty' => 1.0, 'cost' => 4.50, 'min' => 20.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Cânula Flexível 25G 25mm',       'unit' => 'unidade', 'qty' => 1.0, 'cost' => 3.80, 'min' => 20.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Cânula Flexível 27G 38mm',       'unit' => 'unidade', 'qty' => 1.0, 'cost' => 5.00, 'min' => 20.0,  'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Gaze Estéril 7.5x7.5cm',        'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.08, 'min' => 200.0, 'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Álcool Isopropílico 70%',        'unit' => 'ml',      'qty' => 1.0, 'cost' => 0.02, 'min' => 500.0, 'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Clorexidina Alcoólica 0.5%',     'unit' => 'ml',      'qty' => 1.0, 'cost' => 0.03, 'min' => 300.0, 'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Micropore 2.5cm',                'unit' => 'unidade', 'qty' => 1.0, 'cost' => 3.50, 'min' => 5.0,   'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Campo Descartável Estéril 50x50cm', 'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.80, 'min' => 20.0, 'buh' => null, 'lot' => false],
            ['cat' => 'Insumo de Procedimento', 'name' => 'Máscara Cirúrgica',              'unit' => 'unidade', 'qty' => 1.0, 'cost' => 0.25, 'min' => 50.0,  'buh' => null, 'lot' => false],
        ];
    }

    // ─────────────────────────────────────────────────────────────

    public function up(): void
    {
        $now = now();

        // ── Fase 1: garantir que todas as categorias existam ──────
        $catMap = [];
        foreach ($this->categorias() as $cat) {
            $id = DB::table('stock_categories')->where('name', $cat['name'])->value('id');
            if (!$id) {
                $id = DB::table('stock_categories')->insertGetId([
                    'name'       => $cat['name'],
                    'color'      => $cat['color'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $catMap[$cat['name']] = $id;
        }

        // ── Fase 2: inserir produtos que ainda não existem ────────
        foreach ($this->produtos() as $p) {
            if (DB::table('stock_products')->where('name', $p['name'])->exists()) {
                continue;
            }

            DB::table('stock_products')->insert([
                'name'                   => $p['name'],
                'category_id'            => $catMap[$p['cat']],
                'unit_type'              => $p['unit'],
                'quantity_per_package'   => $p['qty'],
                'unit_cost'              => $p['cost'],
                'minimum_stock_quantity' => $p['min'],
                'beyond_use_hours'       => $p['buh'],
                'requires_lot_control'   => $p['lot'],
                'active'                 => true,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
        }
    }

    public function down(): void
    {
        $nomesDosCatalogo = array_column($this->produtos(), 'name');
        $hasBatchesTable  = \Illuminate\Support\Facades\Schema::hasTable('stock_batches');

        foreach ($nomesDosCatalogo as $nome) {
            $produto = DB::table('stock_products')->where('name', $nome)->first();
            if (!$produto) {
                continue;
            }

            $temLotes = $hasBatchesTable
                && DB::table('stock_batches')->where('product_id', $produto->id)->exists();

            if (!$temLotes) {
                DB::table('stock_products')->where('id', $produto->id)->delete();
            }
        }

        // Remover apenas as 2 categorias novas, caso fiquem vazias
        foreach (['Peeling & Ácidos', 'Anestésico'] as $catNome) {
            $cat = DB::table('stock_categories')->where('name', $catNome)->first();
            if ($cat && !DB::table('stock_products')->where('category_id', $cat->id)->exists()) {
                DB::table('stock_categories')->where('id', $cat->id)->delete();
            }
        }
    }
};
