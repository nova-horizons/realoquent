<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NovaHorizons\Realoquent\Enums\ColumnType;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

test('mappings are as expected', function (string $connection, ColumnType $type, ColumnType $expectedType) {
    setupDb($connection);
    Schema::dropIfExists('temp_col');
    Schema::create('temp_col', function (Blueprint $table) use ($type) {
        $table->{$type->getMigrationFunction()}('temp');
    });
    $col = getColumn('temp_col', 'temp');
    $detectedType = $col['realoquent_type'];
    Schema::drop('temp_col');
    expect($detectedType->value)->toBe($expectedType->value);

})->with('main-column-types');
