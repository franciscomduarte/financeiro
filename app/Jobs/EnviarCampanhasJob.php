<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Campanhas\EnviarProximaMensagemCampanhaAction;
use App\Services\Campanhas\RitmoCampanhas;
use App\Support\ClinicaAtual;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Envia UMA mensagem de campanha e agenda a si mesmo para daqui a 30–90 s (cadeia).
 * Só existe uma cadeia por clínica: o token em cache identifica a ativa; jobs de cadeias antigas param sozinhos.
 * Fora do horário, no limite do dia ou sem nada na fila, a cadeia termina e o agendador (a cada minuto) recomeça quando der.
 */
class EnviarCampanhasJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 1; // nunca reenvia a mesma mensagem por nova tentativa automática
    public int $timeout = 90;

    public function __construct(public readonly string $token) {}

    public static function chaveCadeia(string $clinicaId): string
    {
        return "campanhas:cadeia:{$clinicaId}";
    }

    /** Começa a cadeia se não houver uma ativa (chamado pelo agendador e ao iniciar/retomar uma campanha). */
    public static function iniciarCadeia(): void
    {
        $chave = self::chaveCadeia((string) app(ClinicaAtual::class)->id());
        $token = (string) Str::uuid();
        if (Cache::add($chave, $token, self::ttlCadeia())) {
            self::dispatch($token)->afterCommit();
        }
    }

    public static function ttlCadeia(): int
    {
        return (int) config('campanhas.intervalo_max_segundos') + 300;
    }

    public function handle(EnviarProximaMensagemCampanhaAction $enviar, RitmoCampanhas $ritmo, ClinicaAtual $clinicaAtual): void
    {
        $chave = self::chaveCadeia((string) $clinicaAtual->id());
        if (Cache::get($chave) !== $this->token) {
            return; // outra cadeia assumiu
        }

        $continua = Cache::lock("campanhas:envio:{$clinicaAtual->id()}", 120)
            ->get(fn () => $enviar->execute());

        if ($continua !== true) {
            Cache::forget($chave); // terminou por agora (horário, limite, fila vazia ou lock ocupado)

            return;
        }

        $espera = $ritmo->intervalo();
        Cache::put($chave, $this->token, $espera + self::ttlCadeia());
        self::dispatch($this->token)->delay(now()->addSeconds($espera));
    }
}
