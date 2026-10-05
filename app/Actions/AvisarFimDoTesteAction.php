<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RoleUsuario;
use App\Enums\StatusClinica;
use App\Mail\FimDoTesteMail;
use App\Models\Clinica;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Rotina diária: avisa os administradores de cada clínica em teste 3 dias antes do fim e no último dia.
 * Cada aviso sai uma única vez (marcado na clínica); se a rotina falhar um dia, o aviso sai no seguinte.
 */
class AvisarFimDoTesteAction
{
    public const DIAS_ANTES = 3;

    /** @return int quantidade de clínicas avisadas */
    public function execute(): int
    {
        $avisadas = 0;

        Clinica::query()
            ->select(['id', 'nome', 'status', 'teste_ate', 'aviso_teste_3_dias_em', 'aviso_teste_fim_em'])
            ->where('status', StatusClinica::Teste->value)
            ->whereNull('aviso_teste_fim_em')
            ->whereBetween('teste_ate', [today()->toDateString(), today()->addDays(self::DIAS_ANTES)->toDateString()])
            ->lazyById(100)
            ->each(function (Clinica $clinica) use (&$avisadas): void {
                try {
                    $avisadas += (int) $this->avisar($clinica);
                } catch (Throwable $e) {
                    Log::error('[FimDoTeste] falha ao avisar clínica', ['tenant_id' => $clinica->id, 'message' => $e->getMessage()]);
                }
            });

        return $avisadas;
    }

    private function avisar(Clinica $clinica): bool
    {
        $diasParaFim = (int) today()->diffInDays($clinica->teste_ate, false);
        $ultimoDia   = $diasParaFim === 0;

        if (! $ultimoDia && $clinica->aviso_teste_3_dias_em !== null) {
            return false;
        }

        $admins = $clinica->usuarios()
            ->select(['users.id', 'users.email'])
            ->wherePivot('papel', RoleUsuario::Admin->value)
            ->where('users.active', true)
            ->limit(20)
            ->pluck('users.email');

        foreach ($admins as $email) {
            Mail::to($email)->queue(new FimDoTesteMail($clinica, $diasParaFim));
        }

        $clinica->forceFill($ultimoDia
            ? ['aviso_teste_fim_em' => now(), 'aviso_teste_3_dias_em' => $clinica->aviso_teste_3_dias_em ?? now()]
            : ['aviso_teste_3_dias_em' => now()])->save();

        Log::info('[FimDoTeste] administradores avisados', [
            'tenant_id'     => $clinica->id,
            'dias_para_fim' => $diasParaFim,
            'destinatarios' => $admins->count(),
        ]);

        return true;
    }
}
