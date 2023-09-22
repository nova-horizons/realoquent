<?php

use NovaHorizons\Realoquent\RealoquentManager;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('can handle configuration', function () {
    setupDb('sqlite');
    $manager = new RealoquentManager(realoquentConfig());
    $schemaManager = $manager->getSchemaManager();

    expect($schemaManager->getSchemaPath())->toEndWith('tests/config/schema.php');
    expect($schemaManager->getSchemaSnapshotPath())->toBe('/tmp/realoquent/storage/schema.php');

    // setupDb creates this:
    expect($schemaManager->schemaExists())->toBe(true);
    // But doesn't snapshot
    expect($schemaManager->schemaSnapshotExists())->toBe(false);
});

it('can create snapshot', function () {
    setupDb('sqlite');
    $manager = new RealoquentManager(realoquentConfig());
    $schemaManager = $manager->getSchemaManager();
    expect(file_exists($schemaManager->getSchemaSnapshotPath()))->toBe(false);
    $schemaManager->makeSchemaSnapshot();
    expect(file_exists($schemaManager->getSchemaSnapshotPath()))->toBe(true);
    unlink($schemaManager->getSchemaSnapshotPath());
});

it('can load schema snapshot', function () {
    setupDb('sqlite');
    $manager = new RealoquentManager(realoquentConfig());
    $schemaManager = $manager->getSchemaManager();
    $schemaManager->makeSchemaSnapshot();
    $schema = $schemaManager->loadSchemaSnapshot();
    expect($schema->getTables())->toHaveKeys(['users', 'teams']);
    unlink($schemaManager->getSchemaSnapshotPath());
});

it('can load schema', function () {
    setupDb('sqlite');
    $manager = new RealoquentManager(realoquentConfig());
    $schemaManager = $manager->getSchemaManager();
    $schema = $schemaManager->loadSchema();
    expect($schema->getTables())->toHaveKeys(['users', 'teams']);
});
