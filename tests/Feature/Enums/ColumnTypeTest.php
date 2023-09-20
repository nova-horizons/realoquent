<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NovaHorizons\Realoquent\Enums\ColumnType;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

test('mappings are symmetrical', function (string $connection, ColumnType $type) {
    setupDb($connection);
    Schema::dropIfExists('temp_col');
    Schema::create('temp_col', function (Blueprint $table) use ($type) {
        $table->{$type->getMigrationFunction()}('temp');
    });
    $col = getColumn('temp_col', 'temp')->getType();
    Schema::drop('temp_col');
    expect(ColumnType::fromDBAL($col)->value)->toBe($type->value);

})->with(['mysql'])->with(fn () => [
    // Popular types listed below
    // TODO Commented out types that do not map symmertrically due to DBAL abstracting type (Timestamp > DateTime, tinyText > TEXT)
    ColumnType::bigInteger,
    ColumnType::binary,
    ColumnType::boolean,
    //ColumnType::char,
    //ColumnType::dateTimeTz,
    ColumnType::dateTime,
    ColumnType::date,
    ColumnType::decimal,
    //ColumnType::double,
    ColumnType::float,
    ColumnType::integer,
    ColumnType::json,
    //ColumnType::jsonb,
    //ColumnType::longText,
    //ColumnType::mediumInteger,
    //ColumnType::mediumText,
    ColumnType::smallInteger,
    ColumnType::string,
    //ColumnType::timeTz,
    ColumnType::time,
    //ColumnType::timestampTz,
    //ColumnType::timestamp,
    //ColumnType::tinyInteger,
    //ColumnType::tinyText,
    //ColumnType::year,
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
