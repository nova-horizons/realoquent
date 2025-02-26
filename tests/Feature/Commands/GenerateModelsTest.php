<?php

use NovaHorizons\Realoquent\Commands\GenerateModels;
use NovaHorizons\Realoquent\RealoquentManager;
use Tests\TestCase\RealoquentTestClass;

use function Pest\Laravel\artisan;

uses(RealoquentTestClass::class);

it('errors when no schema', function () {
    $schema = (new RealoquentManager(realoquentConfig()))->getSchemaManager()->getSchemaSnapshotPath();
    if (file_exists($schema)) {
        unlink($schema);
    }
    artisan('realoquent:generate-models --force')
        ->expectsOutputToContain('Schema file does not exist')
        ->assertExitCode(1);
});

it('errors when schema modified', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $manager->getSchemaManager()->makeSchemaSnapshot();

    modifySchema($manager, function (string $schema): string {
        return str_replace("'users'", "'users2'", $schema);
    });

    artisan(GenerateModels::class)
        ->expectsOutputToContain('Schema has been modified')
        ->assertExitCode(1);
});

it('errors when duplicate ids', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $manager->getSchemaManager()->makeSchemaSnapshot();

    causeDuplicateIds($manager);

    artisan(GenerateModels::class)
        ->expectsOutputToContain('Schema has been modified')
        ->assertExitCode(1);
});
