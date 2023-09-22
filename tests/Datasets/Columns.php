<?php

use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use NovaHorizons\Realoquent\Enums\ColumnType;

dataset('column-and-casts', [
    // ColumnType $type, ?string $cast, string $phpType
    [ColumnType::integer, null, 'integer'],
    [ColumnType::dateTime, null, Carbon::class],
    [ColumnType::dateTime, 'immutable_datetime', Carbon::class],
    [ColumnType::json, 'array', 'array'],
    [ColumnType::json, 'collection', Collection::class],
    [ColumnType::string, AsStringable::class, \Illuminate\Support\Stringable::class],
    [ColumnType::json, AsArrayObject::class, ArrayObject::class],
    [ColumnType::json, 'encrypted:collection', Collection::class],
]);
