<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AcaoClinicaEvento;
use App\Enums\StatusClinica;
use App\Models\Clinica;
use App\Models\ClinicaEvento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Ações do dono da plataforma sobre uma clínica: ativar a assinatura, bloquear, desbloquear e
 * estender o teste grátis. Cada uma grava o histórico da clínica na mesma transação.
 */
class AlterarSituacaoClinicaAction
{
    public const MAX_DIAS_EXTENSAO = 90;

    public function ativar(Clinica $clinica): Clinica
    {
        return $this->alterar($clinica, AcaoClinicaEvento::Ativacao, fn (Clinica $c) => [
            'status'     => StatusClinica::Ativa,
            'ativada_em' => $c->ativada_em ?? now(),
        ]);
    }

    public function bloquear(Clinica $clinica, ?string $motivo = null): Clinica
    {
        return $this->alterar($clinica, AcaoClinicaEvento::Bloqueio, fn () => [
            'status' => StatusClinica::Bloqueada,
        ], array_filter(['motivo' => $motivo]));
    }

    /** Volta para "ativa" se já foi ativada alguma vez; senão, volta para o teste. */
    public function desbloquear(Clinica $clinica): Clinica
    {
        return $this->alterar($clinica, AcaoClinicaEvento::Desbloqueio, fn (Clinica $c) => [
            'status' => $c->ativada_em ? StatusClinica::Ativa : StatusClinica::Teste,
        ]);
    }

    /**
     * Dá mais dias de teste: conta a partir do fim atual (ou de hoje, se o teste já acabou).
     * Reabre os avisos de fim de teste para o novo prazo.
     */
    public function estenderTeste(Clinica $clinica, int $dias): Clinica
    {
        if ($dias < 1 || $dias > self::MAX_DIAS_EXTENSAO) {
            throw new InvalidArgumentException('Informe de 1 a ' . self::MAX_DIAS_EXTENSAO . ' dias.');
        }

        return $this->alterar($clinica, AcaoClinicaEvento::TesteEstendido, function (Clinica $c) use ($dias): array {
            $base = $c->teste_ate !== null && $c->teste_ate->gte(today()) ? $c->teste_ate : today()->subDay();

            return [
                'status'                => StatusClinica::Teste,
                'teste_ate'             => $base->copy()->addDays($dias),
                'aviso_teste_3_dias_em' => null,
                'aviso_teste_fim_em'    => null,
            ];
        }, ['dias' => $dias]);
    }

    /**
     * @param  callable(Clinica): array<string, mixed>  $novosValores
     */
    private function alterar(Clinica $clinica, AcaoClinicaEvento $acao, callable $novosValores, array $detalhes = []): Clinica
    {
        return DB::transaction(function () use ($clinica, $acao, $novosValores, $detalhes): Clinica {
            $clinica = Clinica::query()->lockForUpdate()->findOrFail($clinica->id);
            $antes   = $clinica->status;

            $clinica->forceFill($novosValores($clinica))->save();

            $detalhes += ['de' => $antes->value, 'para' => $clinica->status->value];
            if ($acao === AcaoClinicaEvento::TesteEstendido) {
                $detalhes['teste_ate'] = $clinica->teste_ate->toDateString();
            }
            ClinicaEvento::registrar($clinica, $acao, $detalhes);

            Log::info('[Plataforma] situação da clínica alterada', [
                'tenant_id' => $clinica->id,
                'user_id'   => auth()->id(),
                'acao'      => $acao->value,
                'detalhes'  => $detalhes,
            ]);

            return $clinica;
        });
    }
}
