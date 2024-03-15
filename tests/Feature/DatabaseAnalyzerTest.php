<?php

use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('reverse engineers all default values correctly', function (string $connection) {
    setupDbAndSchema($connection);

    expect(true)->toBeFalse();
})->with('databases')->skip(); // TODO-DBAL
