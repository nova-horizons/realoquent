<?php

use NovaHorizons\Realoquent\RealoquentManager;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('errors when no schema', function () {
    $schema = (new RealoquentManager(realoquentConfig()))->getSchemaManager()->getSchemaSnapshotPath();
    if (file_exists($schema)) {
        unlink($schema);
    }
    $this->artisan('realoquent:generate-models --force')
        ->expectsOutputToContain('Schema file does not exist')
        ->assertExitCode(1);
});
