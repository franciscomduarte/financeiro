<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A aplicação passa a usar o horário de Brasília (antes: UTC). Os carimbos gravados até aqui
 * estavam em UTC e são convertidos para a hora local. Ficam de fora os horários da agenda e dos
 * bloqueios, que sempre foram gravados na hora local da clínica.
 */
return new class extends Migration
{
    private const FUSO = 'America/Sao_Paulo';

    /** Colunas que já estão em hora local */
    private const JA_LOCAIS = [
        'agendamentos.inicio_em', 'agendamentos.fim_em',
        'bloqueios_agenda.inicio_em', 'bloqueios_agenda.fim_em',
    ];

    public function up(): void
    {
        $this->converter("(%s AT TIME ZONE 'UTC') AT TIME ZONE '" . self::FUSO . "'");
    }

    public function down(): void
    {
        $this->converter("(%s AT TIME ZONE '" . self::FUSO . "') AT TIME ZONE 'UTC'");
    }

    private function converter(string $expressao): void
    {
        $colunas = DB::select("
            SELECT table_name, column_name FROM information_schema.columns
            WHERE table_schema = current_schema() AND data_type = 'timestamp without time zone'
            ORDER BY table_name, column_name
        ");

        foreach (collect($colunas)->groupBy('table_name') as $tabela => $daTabela) {
            $sets = $daTabela
                ->reject(fn ($c) => in_array("{$tabela}.{$c->column_name}", self::JA_LOCAIS, true))
                ->map(fn ($c) => '"' . $c->column_name . '" = ' . sprintf($expressao, '"' . $c->column_name . '"'))
                ->values();

            if ($sets->isEmpty()) {
                continue;
            }
            // Uma atualização por tabela; NULL continua NULL
            DB::statement(sprintf('UPDATE "%s" SET %s', $tabela, $sets->implode(', ')));
        }
    }
};
