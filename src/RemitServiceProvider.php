<?php

namespace Fintech\Remit;

use Fintech\Remit\Commands\InstallCommand;
use Fintech\Remit\Commands\RemitCommand;
use Illuminate\Support\ServiceProvider;

class RemitServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/remit.php', 'fintech.remit'
        );

        $this->app->register(RouteServiceProvider::class);
        $this->app->register(RepositoryServiceProvider::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/remit.php' => config_path('fintech/remit.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'remit');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/remit'),
        ]);

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'remit');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/remit'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                RemitCommand::class,
            ]);
        }
    }
}
