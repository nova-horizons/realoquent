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

    protected function tearDown(): void
    {
        // Delete schema.php
        $schema = realoquentConfig()['schema_dir'].DIRECTORY_SEPARATOR.'schema.php';
        if (file_exists($schema)) {
            unlink($schema);
        }

        // Delete tables/*.php
        $this->rmDir(realoquentConfig()['schema_dir'].DIRECTORY_SEPARATOR.'tables');

        // Delete BaseModels/*.php
        $this->rmDir(realoquentConfig()['model_dir'].DIRECTORY_SEPARATOR.'BaseModels');

        // Delete BaseModels/*.php
        $this->rmDir(realoquentConfig()['model_dir'].DIRECTORY_SEPARATOR.'BaseModels');

        $this->rmDir(realoquentConfig()['migrations_dir']);
        $this->rmDir(realoquentConfig()['storage_dir']);

        parent::tearDown();
    }

    private function rmDir(string $dir): void
    {
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }
                unlink($dir.DIRECTORY_SEPARATOR.$file);
            }
            rmdir($dir);
        }
    }
}
