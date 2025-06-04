<?php

namespace Prism\Prism;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Prism\Prism\Telemetry\TelemetryManager;

class PrismServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/prism.php' => config_path('prism.php'),
        ], 'prism-config');

        if (config('prism.prism_server.enabled')) {
            Route::group([
                'middleware' => config('prism.prism_server.middleware', []),
            ], function (): void {
                $this->loadRoutesFrom(__DIR__.'/Routes/PrismServer.php');
            });
        }
    }

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/prism.php',
            'prism'
        );

        $this->app->singleton(
            PrismManager::class,
            fn (): PrismManager => new PrismManager($this->app)
        );

        $this->app->alias(PrismManager::class, 'prism-manager');

        $this->app->singleton(
            'prism-server',
            fn (): PrismServer => new PrismServer
        );

        $this->app->singleton(
            'prism-telemetry',
            fn ($app): TelemetryManager => new TelemetryManager($app)
        );

        $this->app->alias('prism-telemetry', TelemetryManager::class);
    }
}
