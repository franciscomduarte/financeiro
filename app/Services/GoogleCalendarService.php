<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\Profissional;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleCalendarService
{
    private const TIMEZONE = 'America/Sao_Paulo';

    public function criarEvento(Agendamento $agendamento): ?string
    {
        $profissional = $agendamento->profissional;
        $client       = $this->getClient($profissional);

        if ($client === null) {
            return null;
        }

        try {
            $service  = new Calendar($client);
            $event    = $this->buildEvent($agendamento);
            $created  = $service->events->insert($profissional->google_calendar_id, $event);

            return $created->getId();
        } catch (Throwable $e) {
            Log::warning('GoogleCalendar: falha ao criar evento', [
                'agendamento' => $agendamento->id,
                'error'       => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function atualizarEvento(Agendamento $agendamento): void
    {
        if (! $agendamento->google_event_id) {
            return;
        }

        $profissional = $agendamento->profissional;
        $client       = $this->getClient($profissional);

        if ($client === null) {
            return;
        }

        try {
            $service = new Calendar($client);
            $event   = $this->buildEvent($agendamento);
            $service->events->update($profissional->google_calendar_id, $agendamento->google_event_id, $event);
        } catch (Throwable $e) {
            Log::warning('GoogleCalendar: falha ao atualizar evento', [
                'agendamento'    => $agendamento->id,
                'google_event_id' => $agendamento->google_event_id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    public function deletarEvento(string $eventId, string $calendarId): void
    {
        // Requer um client genérico; chamador deve garantir que calendarId é válido.
        // Usaremos o primeiro profissional que possua refresh_token configurado.
        $profissional = Profissional::whereNotNull('google_refresh_token')
            ->where('google_calendar_id', $calendarId)
            ->first();

        if (! $profissional) {
            return;
        }

        $client = $this->getClient($profissional);
        if ($client === null) {
            return;
        }

        try {
            $service = new Calendar($client);
            $service->events->delete($calendarId, $eventId);
        } catch (Throwable $e) {
            Log::warning('GoogleCalendar: falha ao deletar evento', [
                'event_id'    => $eventId,
                'calendar_id' => $calendarId,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function getClient(Profissional $profissional): ?GoogleClient
    {
        if (! $profissional->google_refresh_token || ! $profissional->google_calendar_id) {
            Log::info('GoogleCalendar: profissional sem credenciais configuradas', [
                'profissional' => $profissional->id,
            ]);
            return null;
        }

        $clientId     = config('google.client_id');
        $clientSecret = config('google.client_secret');

        if (! $clientId || ! $clientSecret) {
            Log::warning('GoogleCalendar: GOOGLE_CLIENT_ID ou GOOGLE_CLIENT_SECRET não configurados');
            return null;
        }

        try {
            $client = new GoogleClient();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setAccessType('offline');

            $client->fetchAccessTokenWithRefreshToken($profissional->google_refresh_token);

            return $client;
        } catch (Throwable $e) {
            Log::warning('GoogleCalendar: falha ao obter client', [
                'profissional' => $profissional->id,
                'error'        => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildEvent(Agendamento $agendamento): Event
    {
        $paciente     = $agendamento->paciente;
        $procedimento = $agendamento->procedimento;
        $profissional = $agendamento->profissional;

        $event = new Event([
            'summary'     => "{$paciente->nome} — {$procedimento->nome}",
            'description' => implode("\n", array_filter([
                "Procedimento: {$procedimento->nome}",
                "Profissional: {$profissional->nome}",
                $agendamento->observacoes ? "Observações: {$agendamento->observacoes}" : null,
            ])),
            'colorId'     => $this->mapColorId($agendamento->status),
        ]);

        $startDt = new EventDateTime();
        $startDt->setDateTime($agendamento->inicio_em->toRfc3339String());
        $startDt->setTimeZone(self::TIMEZONE);
        $event->setStart($startDt);

        $endDt = new EventDateTime();
        $endDt->setDateTime($agendamento->fim_em->toRfc3339String());
        $endDt->setTimeZone(self::TIMEZONE);
        $event->setEnd($endDt);

        return $event;
    }

    private function mapColorId(StatusAgendamento $status): string
    {
        return match ($status) {
            StatusAgendamento::Agendado   => '7',  // Peacock (azul)
            StatusAgendamento::Confirmado => '2',  // Sage (verde)
            StatusAgendamento::Realizado  => '10', // Basil (verde escuro)
            StatusAgendamento::Cancelado  => '4',  // Flamingo (vermelho)
            StatusAgendamento::Falta      => '11', // Tomato
            StatusAgendamento::Reagendado => '6',  // Tangerine
        };
    }
}
