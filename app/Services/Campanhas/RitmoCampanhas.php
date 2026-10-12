<?php

declare(strict_types=1);

namespace App\Services\Campanhas;

use App\Enums\StatusDestinatarioCampanha;
use App\Models\CampanhaDestinatario;
use Carbon\CarbonImmutable;

/** Regras de ritmo das campanhas (config/campanhas.php): horário de envio, limite por dia e intervalo sorteado. */
class RitmoCampanhas
{
    public function agora(): CarbonImmutable
    {
        return CarbonImmutable::now((string) config('clinica.fuso_horario'));
    }

    public function dentroDoHorario(?CarbonImmutable $agora = null): bool
    {
        $agora ??= $this->agora();

        return in_array($agora->dayOfWeekIso, (array) config('campanhas.dias_semana'), true)
            && $agora->hour >= (int) config('campanhas.hora_inicio')
            && $agora->hour < (int) config('campanhas.hora_fim');
    }

    /** Mensagens de campanha já enviadas hoje pela clínica ativa (todas as campanhas somadas). */
    public function enviadasHoje(): int
    {
        return CampanhaDestinatario::query()
            ->where('status', StatusDestinatarioCampanha::Enviado->value)
            ->where('enviado_em', '>=', $this->agora()->startOfDay())
            ->count();
    }

    public function limiteDiario(): int
    {
        return max(1, (int) config('campanhas.limite_diario'));
    }

    public function limiteAtingido(): bool
    {
        return $this->enviadasHoje() >= $this->limiteDiario();
    }

    /** Segundos até a próxima mensagem (sorteado para não parecer robô). */
    public function intervalo(): int
    {
        $min = max(5, (int) config('campanhas.intervalo_min_segundos'));

        return random_int($min, max($min, (int) config('campanhas.intervalo_max_segundos')));
    }

    /** Texto curto das regras, para a tela. */
    public function descricao(): string
    {
        return sprintf(
            'Uma mensagem a cada %d a %d segundos, no máximo %d por dia, de segunda a sábado das %dh às %dh.',
            (int) config('campanhas.intervalo_min_segundos'), (int) config('campanhas.intervalo_max_segundos'),
            $this->limiteDiario(), (int) config('campanhas.hora_inicio'), (int) config('campanhas.hora_fim'),
        );
    }

    /** Estimativa em minutos para enviar N mensagens (sem contar pausas da noite e do domingo). */
    public function minutosEstimados(int $mensagens): int
    {
        $medio = ((int) config('campanhas.intervalo_min_segundos') + (int) config('campanhas.intervalo_max_segundos')) / 2;

        return (int) ceil($mensagens * $medio / 60);
    }
}
