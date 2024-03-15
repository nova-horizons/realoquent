<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NovaHorizons\Realoquent\Enums\ColumnType;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('has accurate default precisions', function (string $connection, ColumnType $type) {
    setupDb($connection);
    Schema::dropIfExists('temp_precision');
    Schema::create('temp_precision', function (Blueprint $table) use ($type) {
        $table->{$type->getMigrationFunction()}('temp');
    });
    $colPrecision = getColumnInfo('temp_precision', 'temp');
    Schema::drop('temp_precision');
    expect($colPrecision['precision'])->toBe($type->getDefaultPrecision());

})->with('databases')->with(function () {
    return collect(ColumnType::cases())->filter(fn (ColumnType $type) => $type->supportsPrecision())->toArray();
})->skip(); // TODO-DBAL

it('has accurate default scale', function (string $connection, ColumnType $type) {
    setupDb($connection);
    Schema::dropIfExists('temp_scale');
    Schema::create('temp_scale', function (Blueprint $table) use ($type) {
        $table->{$type->getMigrationFunction()}('temp');
    });
    $colScale = getColumnInfo('temp_scale', 'temp');
    Schema::drop('temp_scale');
    expect($colScale['scale'])->toBe($type->getDefaultPrecision());

})->with('databases')->with(function () {
    return collect(ColumnType::cases())->filter(fn (ColumnType $type) => $type->supportsScale())->toArray();
})->skip(); // TODO-DBAL

it('handles default length on unsupported types', function () {
    expect(ColumnType::date->getDefaultLength())->toBeNull();
});

it('handles default precision on unsupported types', function () {
    expect(ColumnType::string->getDefaultPrecision())->toBeNull();
});

it('handles default scale on unsupported types', function () {
    expect(ColumnType::string->getDefaultScale())->toBeNull();
});
