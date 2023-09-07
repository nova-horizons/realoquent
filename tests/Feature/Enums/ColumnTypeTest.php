<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

    //    $select = DB::getPdo()->query('SELECT * FROM temp_col limit 1;');
    //    $meta = $select->getColumnMeta(0);
    //    ray($meta)->label($connection);
    ////    ray(Schema::getColumnType('temp_col', 'temp'),Schema::getColumnType('temp_col', 'temp2'));

    //    expect(true)->toBeTrue();

})->with('databases')->with(fn () => ColumnType::cases())
    ->todo(); // TODO Some cols don't come in with true type from DBAL. TIMESTAMP > DateTime, MEDIUMINT > int

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
