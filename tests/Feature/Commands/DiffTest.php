<?php

use NovaHorizons\Realoquent\Commands\Diff;
use NovaHorizons\Realoquent\RealoquentManager;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('errors when no schema', function () {
    $schema = (new RealoquentManager(realoquentConfig()))->getSchemaManager()->getSchemaPath();
    if (file_exists($schema)) {
        unlink($schema);
    }
    $this->artisan(Diff::class)
        ->expectsOutputToContain('Schema file does not exist')
        ->assertExitCode(1);
});

it('reports no changes', function () {
    setupDbAndSchema('sqlite');
    $manager = new RealoquentManager(realoquentConfig());
    $manager->getSchemaManager()->makeSchemaSnapshot();
    $this->artisan(Diff::class)
        ->expectsOutputToContain('No changes')
        ->assertExitCode(0);
});
