<?php

use NovaHorizons\Realoquent\Commands\Diff;
use NovaHorizons\Realoquent\DataObjects\Schema;
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

it('errors when no schema snapshot', function () {
    setupDbAndSchema(RL_SQLITE);
    $schema = (new RealoquentManager(realoquentConfig()))->getSchemaManager()->getSchemaSnapshotPath();
    if (file_exists($schema)) {
        unlink($schema);
    }
    $this->artisan(Diff::class)
        ->expectsOutputToContain('Schema snapshot file does not exist')
        ->assertExitCode(1);
});

it('reports no changes', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $manager->getSchemaManager()->makeSchemaSnapshot();
    $this->artisan(Diff::class)
        ->expectsOutputToContain('No changes')
        ->assertExitCode(0);
});

it('bails on no', function () {
    setupDbAndSchema(RL_SQLITE);
    $manager = new RealoquentManager(realoquentConfig());
    $manager->getSchemaManager()->makeSchemaSnapshot();

    $newArray = mockSchema();
    $newArray['users']['indexes']['users_id_username_index']['indexColumns'][] = 'email';
    $new = Schema::fromSchemaArray($newArray);
    $manager->getSchemaManager()->writeSchema($new);

    $this->artisan(Diff::class)
        ->expectsConfirmation('Review the changes above. Proceed?', 'yes')
        ->expectsConfirmation('Generate migrations?', 'yes')
        ->expectsConfirmation('Review the above migration. Proceed? (You will have a chance to edit before running)', 'no')
        ->expectsOutputToContain('Diff aborted')
        ->assertExitCode(0);
});
