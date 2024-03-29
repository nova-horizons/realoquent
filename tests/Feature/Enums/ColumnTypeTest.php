<?php

use NovaHorizons\Realoquent\Enums\ColumnType;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('handles default length on unsupported types', function () {
    expect(ColumnType::date->getDefaultLength())->toBeNull();
});
