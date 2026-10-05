<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class EsqueciSenhaController extends Controller
{
    public function showForm(): View
    {
        return view('auth.esqueci-senha');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function showReset(string $token): View
    {
        return view('auth.redefinir-senha', ['token' => $token]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->update(['password' => $password]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Senha redefinida com sucesso. Faça login.');
        }

        if ($status === Password::INVALID_TOKEN) {
            // Link vencido (60 min), já usado ou substituído por um pedido mais novo
            return back()->withInput($request->only('email'))->withErrors(['token' => 'Este link expirou ou já foi usado. Peça um novo link e use o e-mail mais recente.']);
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
