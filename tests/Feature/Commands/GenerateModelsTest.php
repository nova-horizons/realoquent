<?php

use NovaHorizons\Realoquent\Commands\GenerateModels;
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

it('errors when schema modified', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $manager->getSchemaManager()->makeSchemaSnapshot();

    $schemaPath = $manager->getSchemaManager()->getSchemaPath();
    $schema = file_get_contents($schemaPath);
    $newSchema = str_replace("'users'", "'users2'", $schema);
    file_put_contents($schemaPath, $newSchema);

    $this->artisan(GenerateModels::class)
        ->expectsOutputToContain('Schema has been modified')
        ->assertExitCode(1);
});

it('errors when duplicate ids', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $manager->getSchemaManager()->makeSchemaSnapshot();

    $schemaPath = $manager->getSchemaManager()->getSchemaPath();
    $schema = file_get_contents($schemaPath);
    // Replace IDs with the same
    $pattern = "/'realoquentId'\s*=>\s*'(.*)?',/";
    $replacement = "'realoquentId' => '00000000-0000-0000-0000-000000000000',";

    $newSchema = preg_replace($pattern, $replacement, $schema);
    file_put_contents($schemaPath, $newSchema);

    $this->artisan(GenerateModels::class)
        ->expectsOutputToContain('Schema has been modified')
        ->assertExitCode(1);
});
