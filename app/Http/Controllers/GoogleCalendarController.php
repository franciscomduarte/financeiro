<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Profissional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleCalendarController extends Controller
{
    public function authorize(string $profissionalId): RedirectResponse
    {
        $profissional = Profissional::findOrFail($profissionalId);

        $client = $this->buildClient();
        $client->setState($profissional->id);

        $url = $client->createAuthUrl();

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $code           = $request->query('code');
        $profissionalId = $request->query('state');

        if (! $code || ! $profissionalId) {
            return redirect()->route('agenda.configuracao')
                ->with('erro', 'Autorização cancelada ou inválida.');
        }

        $profissional = Profissional::findOrFail($profissionalId);

        try {
            $client    = $this->buildClient();
            $tokenData = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($tokenData['error'])) {
                Log::warning('GoogleCalendar OAuth: erro ao obter token', $tokenData);
                return redirect()->route('agenda.configuracao')
                    ->with('erro', 'Falha na autorização: ' . ($tokenData['error_description'] ?? $tokenData['error']));
            }

            $refreshToken = $tokenData['refresh_token'] ?? null;
            if (! $refreshToken) {
                $refreshToken = $profissional->google_refresh_token;
            }

            $client->setAccessToken($tokenData);
            $calendarService = new \Google\Service\Calendar($client);
            $calendarId      = $calendarService->calendarList->get('primary')->getId();

            $profissional->update([
                'google_refresh_token' => $refreshToken,
                'google_calendar_id'   => $calendarId,
            ]);

            Log::info('GoogleCalendar OAuth: profissional conectado', [
                'profissional' => $profissional->id,
                'calendar_id'  => $calendarId,
            ]);
        } catch (Throwable $e) {
            Log::error('GoogleCalendar OAuth: callback falhou', [
                'profissional' => $profissionalId,
                'error'        => $e->getMessage(),
            ]);
            return redirect()->route('agenda.configuracao')
                ->with('erro', 'Erro ao conectar Google Calendar: ' . $e->getMessage());
        }

        return redirect()->route('agenda.configuracao')
            ->with('sucesso', "Google Calendar de {$profissional->nome} conectado com sucesso.");
    }

    public function desconectar(string $profissionalId): RedirectResponse
    {
        $profissional = Profissional::findOrFail($profissionalId);

        $profissional->update([
            'google_refresh_token' => null,
            'google_calendar_id'   => null,
        ]);

        return redirect()->route('agenda.configuracao')
            ->with('sucesso', "Google Calendar de {$profissional->nome} desconectado.");
    }

    private function buildClient(): \Google\Client
    {
        $client = new \Google\Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(config('google.redirect_uri'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->addScope(\Google\Service\Calendar::CALENDAR_EVENTS);

        return $client;
    }
}
