<?php

namespace Yousefkadah\Pelecard;

use Illuminate\Support\ServiceProvider;

class PelecardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/pelecard.php',
            'pelecard'
        );

        $this->app->singleton(PelecardClient::class, fn ($app): \Yousefkadah\Pelecard\PelecardClient => new PelecardClient(
            terminal: config('pelecard.terminal'),
            user: config('pelecard.user'),
            password: config('pelecard.password'),
            environment: config('pelecard.environment')
        ));

        $this->app->alias(PelecardClient::class, 'pelecard');
    }

    /**
     * Bootstrap services.
     */
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pelecard');

        if (config('pelecard.webhook.enabled')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pelecard.php' => config_path('pelecard.php'),
            ], 'pelecard-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'pelecard-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/pelecard'),
            ], 'pelecard-views');
        }
    }
}
