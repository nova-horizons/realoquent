<?php

use NovaHorizons\Realoquent\Commands\GenerateSnapshot;
use NovaHorizons\Realoquent\RealoquentManager;
use Tests\TestCase\RealoquentTestClass;

use function Pest\Laravel\artisan;

uses(RealoquentTestClass::class);

it('errors when no schema', function () {
    $schema = (new RealoquentManager(realoquentConfig()))->getSchemaManager()->getSchemaPath();
    if (file_exists($schema)) {
        unlink($schema);
    }
    artisan('realoquent:generate-snapshot')
        ->expectsOutputToContain('Schema file does not exist')
        ->assertExitCode(1);
});

it('errors when schema snapshot exists', function () {
    setupDbAndSchema(RL_SQLITE);
    $schemaManager = (new RealoquentManager(realoquentConfig()))->getSchemaManager();
    $schemaManager->makeSchemaSnapshot();

    expect($schemaManager->schemaSnapshotExists())->toBeTrue();

    artisan(GenerateSnapshot::class)
        ->expectsOutputToContain('Schema Snapshot already exists')
        ->assertExitCode(1);
});

it('succeeds with only-if-missing', function () {
    setupDbAndSchema(RL_SQLITE);
    $schemaManager = (new RealoquentManager(realoquentConfig()))->getSchemaManager();
    $schemaManager->makeSchemaSnapshot();

    expect($schemaManager->schemaSnapshotExists())->toBeTrue();

    artisan(GenerateSnapshot::class, ['--only-if-missing' => true])
        ->expectsOutputToContain('Schema Snapshot already exists')
        ->assertExitCode(0);
});

it('errors when schema modified', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $manager->getSchemaManager()->makeSchemaSnapshot();

    modifySchema($manager, function (string $schema): string {
        return str_replace("'users'", "'users2'", $schema);
    });

    artisan(GenerateSnapshot::class)
        ->expectsOutputToContain('Schema has been modified')
        ->assertExitCode(1);
});

it('generates snapshot', function () {
    setupDbAndSchema(RL_SQLITE);
    $schemaManager = (new RealoquentManager(realoquentConfig()))->getSchemaManager();

    expect($schemaManager->schemaSnapshotExists())->toBeFalse();

    artisan(GenerateSnapshot::class)
        ->expectsOutputToContain('Snapshot generated successfully')
        ->assertExitCode(0);

    expect($schemaManager->schemaSnapshotExists())->toBeTrue();
});

it('generates snapshot with force', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $schemaManager = $manager->getSchemaManager();
    $schemaManager->makeSchemaSnapshot();

    expect($schemaManager->schemaSnapshotExists())->toBeTrue();

    modifySchema($manager, function (string $schema): string {
        return str_replace("'users'", "'usersNewName'", $schema);
    });

    artisan(GenerateSnapshot::class, ['--force' => true])
        ->expectsOutputToContain('Snapshot generated successfully')
        ->assertExitCode(0);

    expect($schemaManager->schemaSnapshotExists())->toBeTrue();

    $snapshot = file_get_contents($schemaManager->getSchemaSnapshotPath());
    expect($snapshot)->toContain('usersNewName');
});

it('errors when duplicate ids', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $manager->getSchemaManager()->makeSchemaSnapshot();

    causeDuplicateIds($manager);

    artisan(GenerateSnapshot::class)
        ->expectsOutputToContain('Schema has been modified')
        ->assertExitCode(1);
});
