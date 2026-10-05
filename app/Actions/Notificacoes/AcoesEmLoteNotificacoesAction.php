<?php

declare(strict_types=1);

namespace App\Actions\Notificacoes;

use App\Enums\StatusNotificacao;
use App\Jobs\EnviarNotificacaoJob;
use App\Models\Notificacao;
use App\Services\NotificacaoService;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Ações sobre várias notificações de uma vez: cancelar e enviar agora (agendadas) e reenviar (histórico). */
class AcoesEmLoteNotificacoesAction
{
    public const LIMITE = 100;

    public function __construct(
        private readonly NotificacaoService $notificacoes,
        private readonly ClinicaAtual $clinicaAtual,
    ) {}

    /** @param  array<int, string>  $ids */
    public function cancelar(array $ids): int
    {
        $ids = $this->validar($ids);

        $total = Notificacao::query()->whereKey($ids)->where('status', StatusNotificacao::Agendada)
            ->update(['status' => StatusNotificacao::Cancelada, 'erro' => 'Cancelada pela clínica.', 'updated_at' => now()]);

        Log::info('[Notificações] canceladas', ['quantidade' => $total, 'user_id' => auth()->id()]);

        return $total;
    }

    /** @param  array<int, string>  $ids */
    public function enviarAgora(array $ids): int
    {
        $ids   = $this->validar($ids);
        $total = 0;

        foreach ($ids as $id) {
            $pegou = Notificacao::query()->whereKey($id)->where('status', StatusNotificacao::Agendada)
                ->update(['status' => StatusNotificacao::Enviando, 'updated_at' => now()]);
            if ($pegou === 1) {
                EnviarNotificacaoJob::dispatch($id)->onQueue('default');
                $total++;
            }
        }

        Log::info('[Notificações] enviadas antes da hora', ['quantidade' => $total, 'user_id' => auth()->id()]);

        return $total;
    }

    /** Reenvia as que já saíram (ou falharam); cada reenvio vira um novo registro. @param  array<int, string>  $ids */
    public function reenviar(array $ids): int
    {
        $ids = $this->validar($ids);

        $originais = Notificacao::query()->whereKey($ids)
            ->whereNotIn('status', [StatusNotificacao::Agendada, StatusNotificacao::Enviando])
            ->limit(self::LIMITE)->get();

        DB::transaction(fn () => $originais->each(fn (Notificacao $n) => $this->notificacoes->reenviar($n)));

        Log::info('[Notificações] reenviadas', ['quantidade' => $originais->count(), 'user_id' => auth()->id()]);

        return $originais->count();
    }

    /** @param  array<int, string>  $ids  @return array<int, string> */
    private function validar(array $ids): array
    {
        $this->clinicaAtual->garantirEscrita();

        $ids = array_values(array_unique(array_filter($ids, fn ($id) => is_string($id) && preg_match('/^[0-9a-f-]{36}$/i', $id))));
        if ($ids === []) {
            throw new RuntimeException('Selecione pelo menos uma notificação.');
        }
        if (count($ids) > self::LIMITE) {
            throw new RuntimeException('Selecione no máximo ' . self::LIMITE . ' notificações por vez.');
        }

        return $ids;
    }
}
