<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\AlertaVencimento;
use App\Enums\RoleUsuario;
use App\Mail\ResumoVencimentosMail;
use App\Services\AlertasVencimentoService;
use App\Services\WhatsAppService;
use App\Support\ClinicaAtual;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/**
 * Envia o resumo diário de vencimentos da clínica ativa por e-mail (admins da clínica)
 * e WhatsApp (número da clínica).
 * Cada canal é marcado como enviado no dia, então uma nova tentativa só reenvia o que falhou.
 */
class EnviarResumoVencimentosAction
{
    /** Itens listados no WhatsApp; o restante vira "+N". */
    private const MAX_ITENS_WHATSAPP = 8;

    public function __construct(
        private readonly AlertasVencimentoService $alertas,
        private readonly WhatsAppService $whatsapp,
        private readonly ClinicaAtual $clinicaAtual,
    ) {}

    /**
     * @return array{itens: int, email: ?bool, whatsapp: ?bool} null = canal não se aplica / já enviado hoje
     */
    public function execute(?CarbonImmutable $hoje = null): array
    {
        $hoje ??= CarbonImmutable::today();
        ['vencidos' => $vencidos, 'a_vencer' => $aVencer] = $this->alertas->levantar($hoje);
        $total = $vencidos->count() + $aVencer->count();

        if ($total === 0) {
            Log::info('[ResumoVencimentos] nada a avisar hoje');
            return ['itens' => 0, 'email' => null, 'whatsapp' => null];
        }

        $resultado = [
            'itens'    => $total,
            'email'    => $this->umaVezPorDia('email', $hoje, fn () => $this->enviarEmail($vencidos, $aVencer, $hoje)),
            'whatsapp' => $this->umaVezPorDia('whatsapp', $hoje, fn () => $this->enviarWhatsApp($vencidos, $aVencer, $hoje)),
        ];

        Log::info('[ResumoVencimentos] resumo processado', $resultado + ['data' => $hoje->toDateString()]);

        $falhas = array_keys(array_filter($resultado, fn ($v) => $v === false));
        if ($falhas !== []) {
            // Deixa o Job tentar de novo; o canal que já foi não é reenviado.
            throw new RuntimeException('Falha ao enviar resumo de vencimentos por: ' . implode(', ', $falhas));
        }

        return $resultado;
    }

    /**
     * Texto do WhatsApp (curto, com os itens mais urgentes).
     *
     * @param  Collection<int, AlertaVencimento>  $vencidos
     * @param  Collection<int, AlertaVencimento>  $aVencer
     */
    public static function textoWhatsApp(Collection $vencidos, Collection $aVencer, CarbonImmutable $hoje): string
    {
        $linhas = ['📅 *Vencimentos — ' . $hoje->format('d/m') . '*'];
        if ($nomeClinica = app(ClinicaAtual::class)->get()?->nome) {
            $linhas[0] .= " · {$nomeClinica}";
        }
        $restantes = self::MAX_ITENS_WHATSAPP;

        foreach ([['🔴 *Já venceram*', $vencidos], ['🟡 *A vencer*', $aVencer]] as [$titulo, $itens]) {
            if ($itens->isEmpty()) {
                continue;
            }
            $linhas[] = '';
            $linhas[] = "{$titulo} ({$itens->count()})";
            foreach ($itens->take(max(0, $restantes)) as $a) {
                $valor    = $a->valorFormatado() ? ' — ' . $a->valorFormatado() : '';
                $linhas[] = "• {$a->titulo}{$valor} ({$a->prazo($hoje)})";
            }
            if ($itens->count() > $restantes) {
                $linhas[] = '• +' . ($itens->count() - max(0, $restantes)) . ' item(ns)';
            }
            $restantes -= $itens->count();
        }

        $linhas[] = '';
        $linhas[] = 'Detalhes no sistema: ' . route('inicio');

        return implode("\n", $linhas);
    }

    /** Executa o envio no máximo uma vez por dia por canal; null se já enviado ou não aplicável. */
    private function umaVezPorDia(string $canal, CarbonImmutable $hoje, callable $enviar): ?bool
    {
        $chave = "resumo-vencimentos:{$this->clinicaAtual->id()}:{$hoje->toDateString()}:{$canal}";
        if (Cache::has($chave)) {
            return null;
        }

        try {
            $ok = $enviar();
        } catch (Throwable $e) {
            Log::error("[ResumoVencimentos] erro no canal {$canal}", ['error' => $e->getMessage()]);
            $ok = false;
        }

        if ($ok === true) {
            Cache::put($chave, true, now()->addHours(36));
        }

        return $ok;
    }

    /** @return bool|null null quando não há administradores ativos */
    private function enviarEmail(Collection $vencidos, Collection $aVencer, CarbonImmutable $hoje): ?bool
    {
        $emails = $this->clinicaAtual->get()->usuarios()
            ->wherePivot('papel', RoleUsuario::Admin->value)
            ->where('users.active', true)
            ->limit(20)
            ->pluck('users.email')
            ->filter()
            ->all();

        if ($emails === []) {
            Log::warning('[ResumoVencimentos] nenhum administrador ativo para receber o e-mail');
            return null;
        }

        Mail::to($emails)->send(new ResumoVencimentosMail($vencidos, $aVencer, $hoje, $this->clinicaAtual->get()->nome));

        return true;
    }

    /** @return bool|null null quando o número não está configurado */
    private function enviarWhatsApp(Collection $vencidos, Collection $aVencer, CarbonImmutable $hoje): ?bool
    {
        $numero = preg_replace('/\D/', '', (string) $this->clinicaAtual->get()->whatsapp_numero);
        if ($numero === '') {
            Log::warning('[ResumoVencimentos] clínica sem número de WhatsApp; WhatsApp ignorado');
            return null;
        }

        return $this->whatsapp->enviarTexto($numero, self::textoWhatsApp($vencidos, $aVencer, $hoje));
    }
}
