<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\ClinicaNaoDefinidaException;
use Illuminate\Validation\DatabasePresenceVerifier;

/**
 * Regras exists:/unique: (string ou Rule::exists/unique) passam a considerar só a clínica ativa
 * nas tabelas da clínica — um ID de outra clínica falha na validação.
 */
class ClinicaPresenceVerifier extends DatabasePresenceVerifier
{
    /** @var array<string, true> */
    private array $tabelasDaClinica;

    /** @param  list<string>  $tabelasDaClinica */
    public function __construct($db, array $tabelasDaClinica, private readonly ClinicaAtual $clinicaAtual)
    {
        parent::__construct($db);
        $this->tabelasDaClinica = array_fill_keys($tabelasDaClinica, true);
    }

    protected function table($table)
    {
        $query = parent::table($table);

        if (isset($this->tabelasDaClinica[$table])) {
            $clinicaId = $this->clinicaAtual->id() ?? throw new ClinicaNaoDefinidaException($table);
            $query->where("{$table}.tenant_id", $clinicaId);
        }

        return $query;
    }
}
