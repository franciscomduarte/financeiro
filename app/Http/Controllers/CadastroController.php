<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CadastrarClinicaAction;
use App\Http\Requests\CadastroClinicaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** "Assine já": autocadastro da clínica com teste grátis. */
class CadastroController extends Controller
{
    public function create(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('dashboard') : view('auth.assine');
    }

    public function store(CadastroClinicaRequest $request, CadastrarClinicaAction $action): RedirectResponse
    {
        ['clinica' => $clinica, 'user' => $user] = $action->execute($request->safe()->except(['site', 'password_confirmation']));

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('clinica_id', $clinica->id);

        return redirect()->route('dashboard')->with('success', 'Bem-vindo! Seu teste grátis vai até ' . $clinica->teste_ate->format('d/m') . '.');
    }
}
