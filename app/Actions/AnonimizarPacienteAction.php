<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AcaoAcessoPaciente;
use App\Enums\StatusPaciente;
use App\Models\Paciente;
use App\Services\RegistroAcessoPaciente;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * LGPD (pedido de exclusão): apaga os dados pessoais e clínicos do paciente e mantém os registros
 * financeiros (exigência fiscal), agora ligados a um paciente sem identificação. Não tem volta.
 */
class AnonimizarPacienteAction
{
    public function __construct(
        private readonly RegistroAcessoPaciente $registro,
        private readonly ClinicaAtual $clinicaAtual,
    ) {}

    public function execute(Paciente $paciente): Paciente
    {
        $this->clinicaAtual->garantirEscrita();

        if ($paciente->anonimizado()) {
            throw new RuntimeException('Este paciente já foi anonimizado.');
        }

        $foto = $paciente->foto_path;

        DB::transaction(function () use ($paciente): void {
            $codigo = mb_strtoupper(mb_substr(str_replace('-', '', $paciente->id), 0, 8));

            $paciente->forceFill([
                'nome'                      => "Paciente anonimizado {$codigo}",
                'cpf'                       => null,
                'data_nascimento'           => null,
                'telefone'                  => null,
                'email'                     => null,
                'foto_path'                 => null,
                'anamnese'                  => null,
                'observacoes'               => null,
                'valor_mensalidade'         => 0,
                'status'                    => StatusPaciente::Inativo,
                'aceita_whatsapp_marketing' => false,
                'aceita_email_marketing'    => false,
                'anonimizado_em'            => now(),
            ])->save();

            // Observações dos atendimentos podem conter dados de saúde
            $paciente->agendamentos()->withoutGlobalScopes([\App\Models\Scopes\ProfissionalScope::class])
                ->update(['observacoes' => null, 'motivo_cancelamento' => null]);

            $this->registro->registrar($paciente, AcaoAcessoPaciente::Anonimizou);
        });

        if ($foto) {
            Storage::disk('public')->delete($foto);
        }

        Log::warning('[LGPD] paciente anonimizado', [
            'paciente_id' => $paciente->id,
            'tenant_id'   => $paciente->tenant_id,
            'user_id'     => auth()->id(),
        ]);

        return $paciente;
    }
}
