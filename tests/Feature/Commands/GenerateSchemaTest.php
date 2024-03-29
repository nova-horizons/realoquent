<?php

use NovaHorizons\Realoquent\Commands\GenerateSchema;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('can run with force', function () {
    $this->artisan('realoquent:generate-schema --force')
        ->expectsOutputToContain('The following models were found in code, but not in the database')
        ->expectsOutputToContain(' - \\'.\Tests\Models\Orphan::class.' (expected table orphans)')
        ->assertExitCode(0);
});

it('can abort when asked', function () {
    setupDbAndSchema(RL_SQLITE);
    $this->artisan(GenerateSchema::class)
        ->expectsConfirmation(' Do you want to overwrite it?', 'no')
        ->assertExitCode(1);
});
