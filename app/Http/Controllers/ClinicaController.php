<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/** Escolha e troca da clínica ativa (multiclínica). */
class ClinicaController extends Controller
{
    public function escolher(Request $request): View
    {
        return view('clinicas.escolher', [
            'clinicas' => $request->user()->clinicas()->get(['clinicas.id', 'nome', 'status']),
        ]);
    }

    public function ativar(Request $request, string $clinica): RedirectResponse
    {
        $escolhida = $request->user()->clinicas()->whereKey($clinica)->firstOrFail();

        $request->session()->put('clinica_id', $escolhida->id);
        Log::info('[Clinica] clínica ativa trocada', ['user_id' => $request->user()->id, 'tenant_id' => $escolhida->id]);

        return redirect()->route('inicio');
    }
}
