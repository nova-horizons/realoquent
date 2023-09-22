<?php

namespace Tests\TestCase;

use NovaHorizons\Realoquent\RealoquentServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as TestBenchTestCase;

class RealoquentTestClass extends TestBenchTestCase
{
    use WithWorkbench;

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('realoquent', realoquentConfig());
    }

    protected function getPackageProviders($app): array
    {
        return [
            RealoquentServiceProvider::class,
        ];
    }
}
