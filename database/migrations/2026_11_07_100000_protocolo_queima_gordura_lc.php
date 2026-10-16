<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Novo procedimento da LC Estética (clínica "lc-estetica"), anunciado no tráfego pago: o assistente passa a responder
 * sobre ele com o preço "a partir de". Não duplica se a clínica já cadastrou; pode ser ajustado em Procedimentos.
 */
return new class extends Migration
{
    private const NOME = 'Protocolo de Queima de Gordura e Flacidez Abdominal';

    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return; // dado real da clínica; os testes montam o próprio cenário
        }

        $tenantId = DB::table('clinicas')->where('slug', 'lc-estetica')->value('id');
        if ($tenantId === null
            || DB::table('procedimentos')->where('tenant_id', $tenantId)->where('nome', 'ilike', '%queima de gordura%')->exists()) {
            return;
        }

        $agora = now();
        DB::table('procedimentos')->insert([
            'tenant_id'       => $tenantId,
            'nome'            => self::NOME,
            'descricao'       => 'Protocolo para queima de gordura e flacidez abdominal que combina Ozonioterapia, ativos e Corrente Russa. '
                . 'Valor a partir de R$ 250,00 por sessão; o número de sessões é definido na avaliação.',
            'duracao_minutos' => 60,
            'valor'           => 250,
            'ativo'           => true,
            'created_at'      => $agora,
            'updated_at'      => $agora,
        ]);
    }

    public function down(): void
    {
        $tenantId = DB::table('clinicas')->where('slug', 'lc-estetica')->value('id');
        if ($tenantId !== null) {
            DB::table('procedimentos')->where('tenant_id', $tenantId)->where('nome', self::NOME)->delete();
        }
    }
};
