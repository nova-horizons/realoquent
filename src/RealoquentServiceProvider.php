<?php

namespace NovaHorizons\Realoquent;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use NovaHorizons\Realoquent\Commands\Diff;
use NovaHorizons\Realoquent\Commands\GenerateModels;
use NovaHorizons\Realoquent\Commands\GenerateSchema;
use NovaHorizons\Realoquent\Commands\GenerateSnapshot;
use NovaHorizons\Realoquent\Commands\NewTable;

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
                Diff::class,
                GenerateModels::class,
                GenerateSchema::class,
                GenerateSnapshot::class,
                NewTable::class,
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
