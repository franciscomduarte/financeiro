<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\VerificacaoEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Confirmação de e-mail do autocadastro (link assinado, funciona sem estar logado). */
class VerificacaoEmailController extends Controller
{
    /** Tela exibida quando o teste terminou e o e-mail ainda não foi confirmado. */
    public function aviso(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard')
            : view('auth.confirmar-email');
    }

    public function reenviar(Request $request, VerificacaoEmailService $verificacao): RedirectResponse
    {
        $verificacao->enviar($request->user());

        return back()->with('success', 'Enviamos um novo link para ' . $request->user()->email . '.');
    }

    public function confirmar(string $id, string $hash, VerificacaoEmailService $verificacao): RedirectResponse
    {
        $user = User::findOrFail($id);
        abort_unless($verificacao->confirmar($user, $hash), 403, 'Link de confirmação inválido.');

        return redirect()->route(Auth::check() ? 'dashboard' : 'login')->with('success', 'E-mail confirmado. Obrigado!');
    }
}
