<?php

namespace Fintech\Remit;

use Fintech\Core\Traits\RegisterPackageTrait;
use Fintech\Remit\Commands\AgraniInstallCommand;
use Fintech\Remit\Commands\InstallCommand;
use Fintech\Remit\Commands\RemitCommand;
use Fintech\Remit\Providers\EventServiceProvider;
use Fintech\Remit\Providers\RepositoryServiceProvider;
use Fintech\Remit\Providers\RouteServiceProvider;
use Illuminate\Support\ServiceProvider;

class RemitServiceProvider extends ServiceProvider
{
    use RegisterPackageTrait;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->packageCode = 'remit';

        $this->mergeConfigFrom(
            __DIR__.'/../config/remit.php', 'fintech.remit'
        );

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RepositoryServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->injectOnConfig();

        $this->publishes([
            __DIR__.'/../config/remit.php' => config_path('fintech/remit.php'),
        ]);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/remit'),
        ]);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/remit'),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'remit');


        $this->loadViewsFrom(__DIR__.'/../resources/views', 'remit');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                AgraniInstallCommand::class,
                RemitCommand::class,
            ]);
        }
    }
}
