<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'admin'   => \App\Http\Middleware\EnsureAdmin::class,
            'clinica' => \App\Http\Middleware\DefinirClinicaAtual::class,
            'super-admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'modulo'      => \App\Http\Middleware\EnsureModulo::class,
        ]);
        // Multiclínica: toda requisição web autenticada (inclusive Livewire) tem clínica ativa
        $middleware->web(append: [\App\Http\Middleware\DefinirClinicaAtual::class]);
        // A clínica ativa precisa existir antes de o Laravel buscar os Models da URL (ex.: anexos/{anexo}),
        // senão o escopo por clínica não tem clínica e a rota quebra (API e rotas com middleware "clinica")
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\DefinirClinicaAtual::class,
        );
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Teste grátis encerrado: recusa a gravação com mensagem clara (não é erro do sistema)
        $exceptions->dontReport(\App\Exceptions\ClinicaSomenteLeituraException::class);
        $exceptions->render(function (\App\Exceptions\ClinicaSomenteLeituraException $e, \Illuminate\Http\Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 403)
                : back()->with('error', $e->getMessage());
        });
    })->create();
