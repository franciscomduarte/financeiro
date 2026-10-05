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
use Illuminate\Support\Facades\View;
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
        $this->app->singleton(\App\Support\EscopoProfissional::class);

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

        // Autocadastro: poucas tentativas por IP (evita criação de contas em massa)
        \Illuminate\Support\Facades\RateLimiter::for('cadastro', fn (\Illuminate\Http\Request $request) => [
            \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->ip()),
            \Illuminate\Cache\RateLimiting\Limit::perDay(20)->by($request->ip()),
        ]);

        // Toda view (telas, e-mails na fila, PDFs) enxerga a clínica ativa como $clinicaAtual
        View::composer('*', fn ($view) => $view->with('clinicaAtual', app(ClinicaAtual::class)->get()));

        // Jobs na fila herdam a clínica de quem os despachou (via Context) e limpam ao terminar
        Context::hydrated(function (ContextRepository $context): void {
            $clinicaId = $context->get('tenant_id');
            app(ClinicaAtual::class)->definir($clinicaId ? Clinica::find($clinicaId) : null);
        });
        Queue::after(fn () => app(ClinicaAtual::class)->definir(null));
        Queue::before(fn () => app(\App\Support\EscopoProfissional::class)->esquecer());
        Queue::failing(fn () => app(ClinicaAtual::class)->definir(null));

        // Ações das telas Livewire passam pelas mesmas regras de acesso da rota da página
        \Livewire\Livewire::addPersistentMiddleware([
            \App\Http\Middleware\EnsureModulo::class,
            \App\Http\Middleware\EnsureAdmin::class,
            \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        // Teste encerrado: ação do Livewire que tenta gravar não vira erro 500 e a tela mostra o motivo
        // no lugar do "Erro ao salvar" genérico (os componentes usam $flashErro) ou num aviso do layout.
        \Livewire\Livewire::listen('exception', function ($component, $e, callable $stopPropagation): void {
            if ($e instanceof \App\Exceptions\ClinicaSomenteLeituraException) {
                $stopPropagation();
            }
        });
        \Livewire\Livewire::listen('call', function ($component) {
            app(ClinicaAtual::class)->consumirEscritaBloqueada();

            return function ($retorno) use ($component) {
                if (app(ClinicaAtual::class)->consumirEscritaBloqueada()) {
                    if (property_exists($component, 'flashErro')) {
                        $component->flashErro = app(ClinicaAtual::class)->mensagemSomenteLeitura();
                        if (property_exists($component, 'flashSucesso')) {
                            $component->flashSucesso = null;
                        }
                    } else {
                        $component->dispatch('clinica-somente-leitura');
                    }
                }

                return $retorno;
            };
        });

        if (app()->isProduction() || str_starts_with(config('app.url', ''), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
