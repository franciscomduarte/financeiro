<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusNotificacao;
use App\Models\Notificacao;
use App\Models\Scopes\ClinicaScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Saúde do sistema (visão da plataforma, todas as clínicas): fila, jobs que falharam,
 * erros no log e notificações com falha. Usado pelo monitor e pelo painel da plataforma.
 */
class SaudeSistemaService
{
    public const CHAVE_BATIMENTO = 'monitor:fila:batimento';
    public const CHAVE_INICIO    = 'monitor:iniciado_em';

    public const FILA_PARADA_MINUTOS      = 15;
    public const JOB_ATRASADO_MINUTOS     = 10;
    public const LIMITE_ERROS_HORA        = 20;
    public const LIMITE_NOTIFICACOES_HORA = 10;

    /**
     * @return array{batimento_minutos: ?int, jobs_atrasados: int, falhas_24h: int, falhas_hora: int,
     *               erros_hora: int, ultimo_erro: ?string, notificacoes_falhas_hora: int, ultimas_falhas: array<int, string>}
     */
    public function metricas(): array
    {
        $batimento = Cache::get(self::CHAVE_BATIMENTO);

        return [
            'batimento_minutos'        => $batimento ? (int) floor((now()->getTimestamp() - (int) $batimento) / 60) : null,
            'jobs_atrasados'           => $this->jobsAtrasados(),
            'falhas_24h'               => $this->falhasDesde(now()->subDay()),
            'falhas_hora'              => $this->falhasDesde(now()->subHour()),
            'erros_hora'               => self::errosNaUltimaHora(),
            'ultimo_erro'              => Cache::get('monitor:erros:ultimo'),
            'notificacoes_falhas_hora' => Notificacao::query()->withoutGlobalScope(ClinicaScope::class)
                ->where('status', StatusNotificacao::Falhou)->where('updated_at', '>=', now()->subHour())->count(),
            'ultimas_falhas'           => DB::table('failed_jobs')->where('failed_at', '>=', now()->subHour())
                ->orderByDesc('failed_at')->limit(5)->get(['payload', 'exception'])
                ->map(fn ($f) => (json_decode((string) $f->payload, true)['displayName'] ?? 'Job') . ': ' . mb_substr(strtok((string) $f->exception, "\n") ?: '', 0, 200))
                ->all(),
        ];
    }

    /**
     * Problemas atuais, por chave (para avisar uma vez e avisar de novo quando normalizar).
     *
     * @return array<string, string>
     */
    public function problemas(): array
    {
        Cache::add(self::CHAVE_INICIO, now()->getTimestamp(), now()->addYear());
        $m = $this->metricas();

        $problemas = [];

        // Sem batimento: só acusa depois que o monitor já está rodando há um tempo (deploy recente)
        $monitorandoHa = (int) floor((now()->getTimestamp() - (int) Cache::get(self::CHAVE_INICIO)) / 60);
        if (($m['batimento_minutos'] ?? PHP_INT_MAX) >= self::FILA_PARADA_MINUTOS && $monitorandoHa >= self::FILA_PARADA_MINUTOS) {
            $problemas['fila_parada'] = $m['batimento_minutos'] === null
                ? 'A fila não está processando tarefas (nenhum sinal de vida registrado). Confira o supervisor: sudo supervisorctl status.'
                : "A fila não processa tarefas há {$m['batimento_minutos']} minutos. Confira o supervisor: sudo supervisorctl status.";
        }
        if ($m['jobs_atrasados'] > 0) {
            $problemas['jobs_atrasados'] = "{$m['jobs_atrasados']} tarefa(s) esperando na fila há mais de " . self::JOB_ATRASADO_MINUTOS . ' minutos.';
        }
        if ($m['falhas_hora'] > 0) {
            $problemas['jobs_falharam'] = "{$m['falhas_hora']} tarefa(s) falharam na última hora:\n• " . implode("\n• ", $m['ultimas_falhas']);
        }
        if ($m['erros_hora'] >= self::LIMITE_ERROS_HORA) {
            $problemas['erros_repetidos'] = "{$m['erros_hora']} erros registrados na última hora. Último: " . ($m['ultimo_erro'] ?? '—');
        }
        if ($m['notificacoes_falhas_hora'] >= self::LIMITE_NOTIFICACOES_HORA) {
            $problemas['notificacoes_falhando'] = "{$m['notificacoes_falhas_hora']} notificações a pacientes falharam na última hora (WhatsApp desconectado ou e-mail recusado?).";
        }

        return $problemas;
    }

    /** Conta erros do log em janelas de 10 minutos (chamado pelo ouvinte de log; nunca lança). */
    public static function registrarErro(string $mensagem): void
    {
        try {
            $chave = 'monitor:erros:' . intdiv(now()->getTimestamp(), 600);
            Cache::add($chave, 0, now()->addHours(2));
            Cache::increment($chave);
            Cache::put('monitor:erros:ultimo', mb_substr($mensagem, 0, 300), now()->addDay());
        } catch (Throwable) {
            // Monitor não pode derrubar quem está registrando o erro
        }
    }

    public static function errosNaUltimaHora(): int
    {
        $agora = intdiv(now()->getTimestamp(), 600);

        return (int) collect(range(0, 5))->sum(fn ($i) => (int) Cache::get('monitor:erros:' . ($agora - $i), 0));
    }

    private function jobsAtrasados(): int
    {
        if (config('queue.default') !== 'database') {
            return 0;
        }

        return DB::table(config('queue.connections.database.table', 'jobs'))
            ->whereNull('reserved_at')
            ->where('available_at', '<=', now()->subMinutes(self::JOB_ATRASADO_MINUTOS)->getTimestamp())
            ->count();
    }

    private function falhasDesde(\DateTimeInterface $desde): int
    {
        return DB::table('failed_jobs')->where('failed_at', '>=', $desde)->count();
    }
}
