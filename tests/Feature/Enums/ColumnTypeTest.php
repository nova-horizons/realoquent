<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NovaHorizons\Realoquent\Enums\ColumnType;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

/**
 * @see TypeDetectorTest for similar test with wider range of types
 */
test('mappings are symmetrical', function (string $connection, ColumnType $type) {
    setupDb($connection);
    Schema::dropIfExists('temp_col');
    Schema::create('temp_col', function (Blueprint $table) use ($type) {
        $table->{$type->getMigrationFunction()}('temp');
    });
    $col = getColumn('temp_col', 'temp')->getType();
    Schema::drop('temp_col');
    expect(ColumnType::fromDBAL($col)->value)->toBe($type->value);

})->with(['mysql', 'pgsql'])->with(fn () => [
    // Popular types listed below
    ColumnType::bigInteger,
    ColumnType::binary,
    ColumnType::boolean,
    ColumnType::dateTime,
    ColumnType::date,
    ColumnType::decimal,
    ColumnType::float,
    ColumnType::integer,
    ColumnType::json,
    ColumnType::smallInteger,
    ColumnType::string,
    ColumnType::time,
]);

it('has accurate default precisions', function (string $connection, ColumnType $type) {
    setupDb($connection);
    Schema::dropIfExists('temp_precision');
    Schema::create('temp_precision', function (Blueprint $table) use ($type) {
        $table->{$type->getMigrationFunction()}('temp');
    });
    $colPrecision = getColumn('temp_precision', 'temp')->getPrecision();
    Schema::drop('temp_precision');
    expect($colPrecision)->toBe($type->getDefaultPrecision());

})->with('databases')->with(function () {
    return collect(ColumnType::cases())->filter(fn (ColumnType $type) => $type->supportsPrecision())->toArray();
});

it('has accurate default scale', function (string $connection, ColumnType $type) {
    setupDb($connection);
    Schema::dropIfExists('temp_scale');
    Schema::create('temp_scale', function (Blueprint $table) use ($type) {
        $table->{$type->getMigrationFunction()}('temp');
    });
    $colScale = getColumn('temp_scale', 'temp')->getScale();
    Schema::drop('temp_scale');
    expect($colScale)->toBe($type->getDefaultScale());

})->with('databases')->with(function () {
    return collect(ColumnType::cases())->filter(fn (ColumnType $type) => $type->supportsScale())->toArray();
});

it('handles default length on unsupported types', function () {
    expect(ColumnType::date->getDefaultLength())->toBeNull();
});

it('handles default precision on unsupported types', function () {
    expect(ColumnType::string->getDefaultPrecision())->toBeNull();
});

it('handles default scale on unsupported types', function () {
    expect(ColumnType::string->getDefaultScale())->toBeNull();
});
