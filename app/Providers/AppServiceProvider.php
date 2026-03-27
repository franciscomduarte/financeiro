<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        if (app()->isProduction()) {
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
            \Illuminate\Support\Facades\URL::forceScheme('https');
            request()->server->set('HTTPS', 'on');
        }
    }
}
