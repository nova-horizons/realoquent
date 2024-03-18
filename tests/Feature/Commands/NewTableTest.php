<?php

use NovaHorizons\Realoquent\DataObjects\Table;
use NovaHorizons\Realoquent\RealoquentManager;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('errors when no schema', function () {
    $schema = (new RealoquentManager(realoquentConfig()))->getSchemaManager()->getSchemaSnapshotPath();
    if (file_exists($schema)) {
        unlink($schema);
    }
    $this->artisan('realoquent:new-table new_table')
        ->expectsOutputToContain('Schema file does not exist')
        ->assertExitCode(1);
});

it('writes schema', function () {
    setupDbAndSchema(RL_SQLITE);
    $schema = (new RealoquentManager(realoquentConfig()))->getSchemaManager()->loadSchema();
    expect(isset($schema->getTables()['new_table']))->toBeFalse();

    $this->artisan('realoquent:new-table new_table')
        ->assertExitCode(0);

    $schema = (new RealoquentManager(realoquentConfig()))->getSchemaManager()->loadSchema();
    expect($schema->getTables()['new_table'])->toBeInstanceOf(Table::class);
});
