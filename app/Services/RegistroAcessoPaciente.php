<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AcaoAcessoPaciente;
use App\Models\Paciente;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\DB;

/**
 * Registra o acesso aos dados do paciente (LGPD). Grava direto na tabela: o registro vale até em
 * modo somente leitura (teste encerrado, suporte), quando o Model não pode gravar. Abrir a mesma
 * ficha várias vezes seguidas conta uma vez a cada 10 minutos.
 */
class RegistroAcessoPaciente
{
    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    public function registrar(Paciente $paciente, AcaoAcessoPaciente $acao): void
    {
        $base = [
            'tenant_id'   => $paciente->tenant_id ?? $this->clinicaAtual->id(),
            'paciente_id' => $paciente->id,
            'user_id'     => auth()->id(),
            'acao'        => $acao->value,
        ];

        if ($acao === AcaoAcessoPaciente::Visualizou && DB::table('paciente_acessos')
            ->where($base)->where('created_at', '>=', now()->subMinutes(10))->exists()) {
            return;
        }

        DB::table('paciente_acessos')->insert($base + ['ip' => request()?->ip(), 'created_at' => now()]);
    }
}
