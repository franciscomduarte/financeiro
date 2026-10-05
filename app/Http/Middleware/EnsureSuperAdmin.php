<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Painel da plataforma: somente o dono (users.is_super_admin, definido por comando no servidor). */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403, 'Acesso restrito ao dono da plataforma.');

        return $next($request);
    }
}
