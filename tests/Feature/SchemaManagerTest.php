<?php

use NovaHorizons\Realoquent\RealoquentManager;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('can handle configuration', function () {
    setupDbAndSchema(RL_SQLITE);
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
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $schemaManager = $manager->getSchemaManager();
    expect($schemaManager->getSchemaSnapshotPath())->not->toBeFile();
    $schemaManager->makeSchemaSnapshot();
    expect($schemaManager->getSchemaSnapshotPath())->toBeFile();
});

it('can write schema', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $schemaManager = $manager->getSchemaManager();
    // Write is handled in setupDbAndSchema
    expect($schemaManager->getSchemaPath())->toBeFile();
});

it('can write split schemas', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $schemaManager = $manager->getSchemaManager();
    expect($schemaManager->getSplitSchemaPath())->not->toBeDirectory();
    $schemaManager->writeSchema($schemaManager->loadSchema(), splitTables: true);
    expect($schemaManager->getSplitSchemaPath().'/users.php')->toBeFile();
    expect($schemaManager->getSplitSchemaPath().'/team_list.php')->toBeFile();
});

it('can load schema snapshot', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $schemaManager = $manager->getSchemaManager();
    $schemaManager->makeSchemaSnapshot();
    $schema = $schemaManager->loadSchemaSnapshot();
    expect($schema->getTables())->toHaveKeys(['users', 'team_list']);
});

it('can load schema', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $schemaManager = $manager->getSchemaManager();
    $schema = $schemaManager->loadSchema();
    expect($schema->getTables())->toHaveKeys(['users', 'team_list']);
});
