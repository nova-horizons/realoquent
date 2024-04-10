<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('reverse engineers all default values correctly', function (string $connection, string $migrationFunction, mixed $default, mixed $expectedDefault) {
    setupDbAndSchema($connection);
    Schema::dropIfExists('temp_col');
    Schema::create('temp_col', function (Blueprint $table) use ($migrationFunction, $default) {
        $table->{$migrationFunction}('temp')->default($default);
    });
    $col = getColumn('temp_col', 'temp');
    Schema::drop('temp_col');
    expect($col['default'])->toBe($expectedDefault);
})->with('default-value-tests');

it('handles nullable defaults correctly', function (string $connection) {
    setupDbAndSchema($connection);
    Schema::dropIfExists('temp_col');
    Schema::create('temp_col', function (Blueprint $table) {
        $table->string('temp')->nullable();
    });
    $col = getColumn('temp_col', 'temp');
    Schema::drop('temp_col');
    expect($col['default'])->toBeNull();
})->with('databases');
