<?php

namespace NovaHorizons\Realoquent;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use NovaHorizons\Realoquent\Commands\Diff;
use NovaHorizons\Realoquent\Commands\GenerateModels;
use NovaHorizons\Realoquent\Commands\GenerateSchema;

class RealoquentServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(RealoquentManager::class, function () {
            return new RealoquentManager(config('realoquent'));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/realoquent.php' => config_path('realoquent.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateModels::class,
                GenerateSchema::class,
                Diff::class,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            RealoquentManager::class,
        ];
    }
}
