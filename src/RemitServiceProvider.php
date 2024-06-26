<?php

namespace Fintech\Remit;

use Fintech\Core\Traits\RegisterPackageTrait;
use Fintech\Remit\Commands\AgraniInstallCommand;
use Fintech\Remit\Commands\InstallCommand;
use Fintech\Remit\Commands\RemitCommand;
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

        $this->app->register(\Fintech\Remit\Providers\EventServiceProvider::class);
        $this->app->register(\Fintech\Remit\Providers\RepositoryServiceProvider::class);
        $this->app->register(\Fintech\Remit\Providers\RouteServiceProvider::class);
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
                AgraniInstallCommand::class,
                RemitCommand::class,
            ]);
        }
    }
}
