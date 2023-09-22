<?php

use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Support\Carbon;
use NovaHorizons\Realoquent\DataObjects\Column;
use NovaHorizons\Realoquent\Enums\ColumnType;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('returns correct phpdoc type', function (ColumnType $type, string $phpType) {
    expect((new Column(
        name: 'id',
        tableName: 'users',
        type: $type,
    ))->getPhpType())->toBe($phpType);
})->with([
    [ColumnType::bigInteger, 'integer'],
    [ColumnType::integer, 'integer'],
    [ColumnType::dateTime, Carbon::class],
    [ColumnType::timestamp, Carbon::class],
    [ColumnType::float, 'float'],
    [ColumnType::string, 'string'],
    [ColumnType::json, 'mixed'],
]);

it('returns correct nullable phpdoc type', function (ColumnType $type, string $phpType) {
    expect((new Column(
        name: 'id',
        tableName: 'users',
        type: $type,
        nullable: true,
    ))->getPhpType())->toBe($phpType);
})->with([
    [ColumnType::bigInteger, '?integer'],
    [ColumnType::integer, '?integer'],
    [ColumnType::dateTime, '?'.Carbon::class],
    [ColumnType::timestamp, '?'.Carbon::class],
    [ColumnType::float, '?float'],
    [ColumnType::string, '?string'],
    [ColumnType::json, '?mixed'],
]);

it('returns user-specified casts', function (ColumnType $type, string $cast, string $phpType) {
    expect((new Column(
        name: 'id',
        tableName: 'users',
        type: $type,
    ))->getPhpType())->toBe($phpType);
})->with([
    [ColumnType::json, 'array', 'array'],
    [ColumnType::json, AsArrayObject::class, ArrayObject::class],
])->todo(); // TODO Fix this test

it('can generate validation', function (string $expectedValidation, Column $column) {
    expect(implode('|', $column->generateDefaultValidation()))->toBe($expectedValidation);
})->with([
    ['required|integer', new Column('id', 'users', ColumnType::integer)],
    ['required|integer|min:0', new Column('id', 'users', ColumnType::integer, unsigned: true)],
    ['required|integer|min:0', new Column('id', 'users', ColumnType::unsignedInteger)],
    ['integer', new Column('id', 'users', ColumnType::integer, nullable: true)],
    ['required|max:'.ColumnType::string->getDefaultLength(), new Column('id', 'users', ColumnType::string)],
    ['required|max:100', new Column('id', 'users', ColumnType::string, length: 100)],
    ['required|ip', new Column('id', 'users', ColumnType::ipAddress)],
    ['required|ulid', new Column('id', 'users', ColumnType::ulid)],
    ['required|uuid', new Column('id', 'users', ColumnType::uuid)],
    ['required|float', fn () => new Column('id', 'users', ColumnType::float)],
    ['required|float|min:0', fn () => new Column('id', 'users', ColumnType::unsignedFloat)],
    ['required|date', new Column('id', 'users', ColumnType::date)],
    ['required|date', fn () => new Column('id', 'users', ColumnType::dateTime)],
    ['required|date', fn () => new Column('id', 'users', ColumnType::timestamp)],
]);

it('can generate pk/unique validation', function (string $expectedValidation, Column $column) {
    expect(implode('|', $column->generateDefaultValidation(true)))->toBe($expectedValidation);
})->with([
    ['required|integer|unique:users', new Column('id', 'users', ColumnType::integer)],
    ['required|uuid|unique:users', new Column('id', 'users', ColumnType::uuid)],
]);
