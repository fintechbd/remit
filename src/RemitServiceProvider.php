<?php

namespace Fintech\Remit;

use Fintech\Core\Traits\RegisterPackageTrait;
use Fintech\Remit\Commands\AgraniBankSetupCommand;
use Fintech\Remit\Commands\CityBankSetupCommand;
use Fintech\Remit\Commands\InstallCommand;
use Fintech\Remit\Commands\IslamiBankSetupCommand;
use Fintech\Remit\Commands\MeghnaBankSetupCommand;
use Fintech\Remit\Commands\RemitCommand;
use Fintech\Remit\Commands\RemitOrderStatusUpdateCommand;
use Fintech\Remit\Providers\EventServiceProvider;
use Fintech\Remit\Providers\RepositoryServiceProvider;
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

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AgraniBankSetupCommand::class,
                CityBankSetupCommand::class,
                IslamiBankSetupCommand::class,
                MeghnaBankSetupCommand::class,
                InstallCommand::class,
                RemitCommand::class,
                RemitOrderStatusUpdateCommand::class
            ]);
        }
    }
}
