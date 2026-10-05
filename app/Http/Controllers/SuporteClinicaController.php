<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AcaoClinicaEvento;
use App\Models\Clinica;
use App\Models\ClinicaEvento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** "Entrar como a clínica": o dono da plataforma vê o sistema da clínica, sempre somente leitura. */
class SuporteClinicaController extends Controller
{
    public const SESSAO = 'suporte_clinica_id';

    public function entrar(Request $request, Clinica $clinica): RedirectResponse
    {
        $request->session()->put(self::SESSAO, $clinica->id);
        ClinicaEvento::registrar($clinica, AcaoClinicaEvento::SuporteEntrada);
        Log::warning('[Plataforma] acesso de suporte iniciado', ['tenant_id' => $clinica->id, 'user_id' => $request->user()->id]);

        return redirect()->route('inicio');
    }

    public function sair(Request $request): RedirectResponse
    {
        $clinicaId = $request->session()->pull(self::SESSAO);
        if ($clinicaId && $clinica = Clinica::find($clinicaId)) {
            ClinicaEvento::registrar($clinica, AcaoClinicaEvento::SuporteSaida);
            Log::info('[Plataforma] acesso de suporte encerrado', ['tenant_id' => $clinica->id, 'user_id' => $request->user()->id]);
        }

        return redirect()->route('plataforma.index');
    }
}
