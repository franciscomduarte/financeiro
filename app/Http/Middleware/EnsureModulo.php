<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Modulo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Libera a rota só para perfis com acesso ao módulo (ver RoleUsuario::modulos()).
 * Página aberta pelo navegador volta para a tela inicial do usuário com um aviso; API e Livewire recebem 403.
 * Também roda nas requisições do Livewire (middleware persistente), então ações nas telas seguem a mesma regra.
 */
class EnsureModulo
{
    public function handle(Request $request, Closure $next, string ...$modulos): Response
    {
        $user = $request->user();
        $pode = $user !== null && collect($modulos)->contains(fn (string $m) => $user->pode(Modulo::from($m)));

        if ($pode) {
            return $next($request);
        }

        $paginaAberta = $request->isMethod('GET') && ! $request->expectsJson() && ! \Livewire\Livewire::isLivewireRequest();
        if ($paginaAberta && $user !== null && ! $request->routeIs($user->paginaInicial())) {
            return redirect()->route($user->paginaInicial())->with('error', 'Seu perfil não tem acesso a essa área.');
        }

        abort(403, 'Seu perfil não tem acesso a essa área.');
    }
}
