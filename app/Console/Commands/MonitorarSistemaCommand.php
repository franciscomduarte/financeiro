<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\AlertaSistemaMail;
use App\Models\Clinica;
use App\Models\User;
use App\Services\SaudeSistemaService;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Roda a cada 5 minutos: confere fila, jobs com falha, erros repetidos e notificações falhando.
 * Avisa o dono da plataforma (e-mail e, se configurado, WhatsApp) uma vez por problema por hora
 * e de novo quando normaliza. O envio é direto (sem fila), porque a fila pode ser o problema.
 */
class MonitorarSistemaCommand extends Command
{
    protected $signature = 'sistema:monitorar';

    protected $description = 'Confere a saúde do sistema e avisa o dono da plataforma';

    private const CHAVE_ATIVOS = 'monitor:problemas_ativos';
    private const REPETIR_APOS_MINUTOS = 60;

    private const NOMES = [
        'fila_parada'           => 'fila de tarefas',
        'jobs_atrasados'        => 'tarefas atrasadas',
        'jobs_falharam'         => 'tarefas com falha',
        'erros_repetidos'       => 'erros no sistema',
        'notificacoes_falhando' => 'notificações aos pacientes',
    ];

    public function handle(SaudeSistemaService $saude): int
    {
        $problemas = $saude->problemas();
        $ativos    = (array) Cache::get(self::CHAVE_ATIVOS, []); // chave => timestamp do último aviso

        $avisar = [];
        foreach ($problemas as $chave => $texto) {
            if (! isset($ativos[$chave]) || now()->getTimestamp() - $ativos[$chave] >= self::REPETIR_APOS_MINUTOS * 60) {
                $avisar[$chave] = $texto;
                $ativos[$chave] = now()->getTimestamp();
            }
        }

        $normalizados = array_keys(array_diff_key($ativos, $problemas));
        foreach ($normalizados as $chave) {
            unset($ativos[$chave]);
        }
        Cache::put(self::CHAVE_ATIVOS, $ativos, now()->addDays(7));

        if ($avisar === [] && $normalizados === []) {
            $this->info($problemas === [] ? 'Tudo certo.' : 'Problemas já avisados: ' . implode(', ', array_keys($problemas)));

            return self::SUCCESS;
        }

        Log::warning('[Monitor] alerta', ['problemas' => array_keys($avisar), 'normalizados' => $normalizados]);
        $this->avisar($avisar, array_map(fn ($c) => self::NOMES[$c] ?? $c, $normalizados));
        $this->info('Aviso enviado.');

        return self::SUCCESS;
    }

    /** @param  array<string, string>  $problemas  @param  array<int, string>  $normalizados */
    private function avisar(array $problemas, array $normalizados): void
    {
        $emails = collect(explode(',', (string) config('services.monitor.emails')))
            ->merge(User::query()->where('is_super_admin', true)->where('active', true)->pluck('email'))
            ->map(fn ($e) => trim((string) $e))->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->unique()->values();

        if ($emails->isNotEmpty()) {
            try {
                Mail::to($emails->all())->send(new AlertaSistemaMail($problemas, $normalizados));
            } catch (Throwable $e) {
                Log::error('[Monitor] falha ao enviar e-mail de alerta', ['erro' => $e->getMessage()]);
            }
        }

        $this->avisarWhatsApp($problemas, $normalizados);
    }

    /** WhatsApp pela instância de uma clínica (MONITOR_WHATSAPP_CLINICA = slug) para o "WhatsApp da gestão" dela. */
    private function avisarWhatsApp(array $problemas, array $normalizados): void
    {
        $slug = (string) config('services.monitor.whatsapp_clinica');
        if ($slug === '') {
            return;
        }

        try {
            $clinica = Clinica::query()->where('slug', $slug)->first();
            if ($clinica === null || blank($clinica->whatsapp_numero)) {
                return;
            }
            $texto = $problemas !== []
                ? "⚠️ *" . config('app.name') . ": problemas no sistema*\n\n" . implode("\n\n", $problemas)
                : '✅ *' . config('app.name') . '*: voltou ao normal (' . implode(', ', $normalizados) . ').';

            app(ClinicaAtual::class)->executarComo($clinica, fn () => app(WhatsAppService::class)->enviarTextoParaTelefone((string) $clinica->whatsapp_numero, mb_substr($texto, 0, 3500)));
        } catch (Throwable $e) {
            Log::error('[Monitor] falha ao enviar WhatsApp de alerta', ['erro' => $e->getMessage()]);
        }
    }
}
