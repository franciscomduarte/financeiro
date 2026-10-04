<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Clinica;
use App\Support\ClinicaAtual;
use App\Support\ClinicaPresenceVerifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Clínica ativa da requisição/job (multiclínica)
        $this->app->singleton(ClinicaAtual::class);

        // exists:/unique: passam a considerar apenas a clínica ativa
        $this->app->extend('validation.presence', fn ($verifier, $app) => new ClinicaPresenceVerifier(
            $app['db'],
            config('clinica.tabelas', []),
            $app->make(ClinicaAtual::class),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        // Jobs na fila herdam a clínica de quem os despachou (via Context) e limpam ao terminar
        Context::hydrated(function (ContextRepository $context): void {
            $clinicaId = $context->get('tenant_id');
            app(ClinicaAtual::class)->definir($clinicaId ? Clinica::find($clinicaId) : null);
        });
        Queue::after(fn () => app(ClinicaAtual::class)->definir(null));
        Queue::failing(fn () => app(ClinicaAtual::class)->definir(null));

        if (app()->isProduction() || str_starts_with(config('app.url', ''), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
