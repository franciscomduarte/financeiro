<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusClinica;
use App\Models\Clinica;
use Illuminate\Support\Facades\DB;

/** Números do painel da plataforma, numa única consulta agregada. */
class IndicadoresPlataformaService
{
    /**
     * @return array{total: int, teste: int, ativas: int, bloqueadas: int, cadastros_mes: int,
     *               testes_acabando: int, conversao: ?float}
     */
    public function calcular(): array
    {
        $hoje   = today()->toDateString();
        $semana = today()->addDays(7)->toDateString();
        $mes    = today()->startOfMonth()->toDateTimeString();

        $r = Clinica::query()->selectRaw(
            'count(*) as total,
             count(*) filter (where status = ?) as teste,
             count(*) filter (where status = ?) as ativas,
             count(*) filter (where status = ?) as bloqueadas,
             count(*) filter (where created_at >= ?) as cadastros_mes,
             count(*) filter (where status = ? and teste_ate between ? and ?) as testes_acabando,
             count(*) filter (where teste_ate is not null and (ativada_em is not null or teste_ate < ?)) as base_conversao,
             count(*) filter (where teste_ate is not null and ativada_em is not null) as convertidas',
            [
                StatusClinica::Teste->value, StatusClinica::Ativa->value, StatusClinica::Bloqueada->value,
                $mes, StatusClinica::Teste->value, $hoje, $semana, $hoje,
            ],
        )->toBase()->first();

        return [
            'total'           => (int) $r->total,
            'teste'           => (int) $r->teste,
            'ativas'          => (int) $r->ativas,
            'bloqueadas'      => (int) $r->bloqueadas,
            'cadastros_mes'   => (int) $r->cadastros_mes,
            'testes_acabando' => (int) $r->testes_acabando,
            // Das clínicas que já passaram pelo teste (acabou ou assinaram), quantas assinaram
            'conversao'       => $r->base_conversao > 0 ? round($r->convertidas / $r->base_conversao * 100, 1) : null,
        ];
    }

    /**
     * Quantidade de registros da clínica (detalhe no painel), ignorando o escopo da clínica ativa.
     *
     * @return array{pacientes: int, agendamentos: int, lancamentos: int}
     */
    public function uso(Clinica $clinica): array
    {
        $contar = fn (string $tabela) => (int) DB::table($tabela)->where('tenant_id', $clinica->id)->count();

        return [
            'pacientes'    => $contar('pacientes'),
            'agendamentos' => $contar('agendamentos'),
            'lancamentos'  => $contar('transacoes'),
        ];
    }
}
