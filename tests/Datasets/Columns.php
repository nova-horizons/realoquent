<?php

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
    [ColumnType::string, AsStringable::class, '\Illuminate\Support\Stringable|string'],
    [ColumnType::json, AsArrayObject::class, '\Illuminate\Database\Eloquent\Casts\ArrayObject|array'],
    [ColumnType::json, 'encrypted:collection', Collection::class],
]);

dataset('main-column-types', function () {
    // Create array like: 'sqlite--bigInteger' => ['sqlite', ColumnType::bigInteger, ColumnType::integer]
    // Value is [connection, column type, expected column type]
    $types = [
        ColumnType::bigInteger,
        ColumnType::binary,
        ColumnType::boolean,
        ColumnType::char,
        ColumnType::dateTime,
        ColumnType::date,
        ColumnType::decimal,
        ColumnType::double,
        ColumnType::float,
        ColumnType::integer,
        ColumnType::json,
        ColumnType::jsonb,
        ColumnType::longText,
        ColumnType::mediumInteger,
        ColumnType::mediumText,
        ColumnType::smallInteger,
        ColumnType::string,
        ColumnType::time,
        ColumnType::timestamp,
        ColumnType::tinyInteger,
        ColumnType::tinyText,
        ColumnType::uuid,
        ColumnType::ulid,
        ColumnType::year,
    ];

    $dataset = [];
    foreach (['sqlite', 'mysql', 'mariadb', 'pgsql'] as $db) {
        foreach ($types as $type) {
            $dataset[$db.'--'.$type->value] = [$db, $type, $type];
        }
    }

    //////////////////////////
    ///
    /// Adjust for Sqlite's lack of support for some column types
    /// Change the expected type to the closest match
    ///

    /**
     * Sqlite
     *
     * @see https://www.sqlite.org/datatype3.html
     * @see vendor/laravel/framework/src/Illuminate/Database/Schema/Grammars/SQLiteGrammar.php
     */
    $dataset['sqlite--bigInteger'][2] = ColumnType::integer;
    $dataset['sqlite--char'][2] = ColumnType::string;
    $dataset['sqlite--double'][2] = ColumnType::float;
    $dataset['sqlite--json'][2] = ColumnType::text;
    $dataset['sqlite--jsonb'][2] = ColumnType::text;
    $dataset['sqlite--longText'][2] = ColumnType::text;
    $dataset['sqlite--mediumInteger'][2] = ColumnType::integer;
    $dataset['sqlite--mediumText'][2] = ColumnType::text;
    $dataset['sqlite--smallInteger'][2] = ColumnType::integer;
    $dataset['sqlite--timestamp'][2] = ColumnType::dateTime;
    $dataset['sqlite--tinyInteger'][2] = ColumnType::integer;
    $dataset['sqlite--tinyText'][2] = ColumnType::text;
    $dataset['sqlite--uuid'][2] = ColumnType::string;
    $dataset['sqlite--ulid'][2] = ColumnType::string;
    $dataset['sqlite--year'][2] = ColumnType::integer;

    /**
     * MySQL
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/data-types.html
     * @see vendor/laravel/framework/src/Illuminate/Database/Schema/Grammars/MySqlGrammar.php
     */
    $dataset['mysql--boolean'][2] = ColumnType::tinyInteger;
    $dataset['mysql--float'][2] = ColumnType::double;
    $dataset['mysql--jsonb'][2] = ColumnType::json;
    $dataset['mysql--uuid'][2] = ColumnType::char;
    $dataset['mysql--ulid'][2] = ColumnType::char;

    /**
     * MariaDB
     *
     * @see https://mariadb.com/kb/en/data-types/
     * @see vendor/laravel/framework/src/Illuminate/Database/Schema/Grammars/MySqlGrammar.php
     */
    $dataset['mariadb--boolean'][2] = ColumnType::tinyInteger;
    $dataset['mariadb--float'][2] = ColumnType::double;
    $dataset['mariadb--json'][2] = ColumnType::longText;
    $dataset['mariadb--jsonb'][2] = ColumnType::longText;
    $dataset['mariadb--uuid'][2] = ColumnType::char;
    $dataset['mariadb--ulid'][2] = ColumnType::char;

    /**
     * Postgres
     *
     * @see https://www.postgresql.org/docs/14/datatype.html
     * @see vendor/laravel/framework/src/Illuminate/Database/Schema/Grammars/PostgresGrammar.php
     */
    $dataset['pgsql--dateTime'][2] = ColumnType::timestamp;
    $dataset['pgsql--float'][2] = ColumnType::double;
    $dataset['pgsql--longText'][2] = ColumnType::text;
    $dataset['pgsql--mediumInteger'][2] = ColumnType::integer;
    $dataset['pgsql--mediumText'][2] = ColumnType::text;
    $dataset['pgsql--tinyInteger'][2] = ColumnType::smallInteger;
    $dataset['pgsql--tinyText'][2] = ColumnType::string;
    $dataset['pgsql--ulid'][2] = ColumnType::char;
    $dataset['pgsql--year'][2] = ColumnType::integer;

    return $dataset;
});
