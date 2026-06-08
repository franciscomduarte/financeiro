<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('procedimentos')->insertOrIgnore([
            ['nome' => 'Ozonioterapia',                                           'duracao_minutos' => 30,  'valor' => 150.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Terapia Neural',                                          'duracao_minutos' => 30,  'valor' => 150.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Botox',                                                   'duracao_minutos' => 60,  'valor' => 850.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Bioestimulador de Colágeno',                              'duracao_minutos' => 90,  'valor' => 1400.00, 'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Fios de PDO',                                             'duracao_minutos' => 120, 'valor' => 900.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Preenchimento de Bigode Chinês',                          'duracao_minutos' => 60,  'valor' => 900.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Protocolo Biozonizado de Cobre O3Cu',                    'duracao_minutos' => 60,  'valor' => 1200.00, 'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Skinbooster',                                             'duracao_minutos' => 60,  'valor' => 350.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Limpeza de Pele com Ozonioterapia',                       'duracao_minutos' => 60,  'valor' => 250.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Microagulhamento com PDRN e Ozonioterapia',               'duracao_minutos' => 90,  'valor' => 350.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Peeling Químico com Ozonioterapia',                       'duracao_minutos' => 60,  'valor' => 280.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Tratamento para Acne com Ativos e Ozonioterapia',         'duracao_minutos' => 60,  'valor' => 270.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Tratamento para Melasma com Ativos e Ozonioterapia',      'duracao_minutos' => 60,  'valor' => 300.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Tratamento para Alopécia/Calvície com Ativos e Ozonioterapia', 'duracao_minutos' => 60, 'valor' => 300.00, 'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Protocolo Recovery',                                      'duracao_minutos' => 60,  'valor' => 280.00,  'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Tratamento para Gordura Localizada com Ativos e Ozonioterapia', 'duracao_minutos' => 60, 'valor' => 250.00, 'ativo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('procedimentos')->whereIn('nome', [
            'Ozonioterapia',
            'Terapia Neural',
            'Botox',
            'Bioestimulador de Colágeno',
            'Fios de PDO',
            'Preenchimento de Bigode Chinês',
            'Protocolo Biozonizado de Cobre O3Cu',
            'Skinbooster',
            'Limpeza de Pele com Ozonioterapia',
            'Microagulhamento com PDRN e Ozonioterapia',
            'Peeling Químico com Ozonioterapia',
            'Tratamento para Acne com Ativos e Ozonioterapia',
            'Tratamento para Melasma com Ativos e Ozonioterapia',
            'Tratamento para Alopécia/Calvície com Ativos e Ozonioterapia',
            'Protocolo Recovery',
            'Tratamento para Gordura Localizada com Ativos e Ozonioterapia',
        ])->delete();
    }
};
