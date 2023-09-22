<?php

use NovaHorizons\Realoquent\Commands\GenerateSchema;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('can run with force', function () {
    $this->artisan('realoquent:generate-schema --force')->assertExitCode(0);
});

it('can abort when asked', function () {
    $this->artisan(GenerateSchema::class)
        ->expectsConfirmation(' Do you want to overwrite it?', 'no')
        ->assertExitCode(1);
});
