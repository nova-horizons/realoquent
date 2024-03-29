<?php

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use NovaHorizons\Realoquent\Enums\ColumnType;

dataset('column-and-casts', [
    // ColumnType $type, ?string $schemaSpecifiedCast, string $phpType
    [ColumnType::integer, null, 'int'],
    [ColumnType::decimal, null, 'float'],
    [ColumnType::decimal, 'decimal:5', 'float'],
    [ColumnType::dateTime, null, Carbon::class],
    [ColumnType::dateTime, 'immutable_datetime', Carbon::class],
    [ColumnType::json, 'array', 'array'],
    [ColumnType::json, 'collection', Collection::class],
    [ColumnType::string, AsStringable::class, '\Illuminate\Support\Stringable|string'],
    [ColumnType::json, AsArrayObject::class, '\Illuminate\Database\Eloquent\Casts\ArrayObject|array'],
    [ColumnType::json, 'encrypted:collection', Collection::class],
]);

dataset('main-column-types', function () {
    // Create array like: RL_SQLITE . '--bigInteger' => ['sqlite', ColumnType::bigInteger, ColumnType::integer]
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
    foreach (RL_DATABASES as $db) {
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
    $dataset[RL_SQLITE.'--bigInteger'][2] = ColumnType::integer;
    $dataset[RL_SQLITE.'--boolean'][2] = ColumnType::tinyInteger;
    $dataset[RL_SQLITE.'--char'][2] = ColumnType::string;
    $dataset[RL_SQLITE.'--json'][2] = ColumnType::text;
    $dataset[RL_SQLITE.'--jsonb'][2] = ColumnType::text;
    $dataset[RL_SQLITE.'--longText'][2] = ColumnType::text;
    $dataset[RL_SQLITE.'--mediumInteger'][2] = ColumnType::integer;
    $dataset[RL_SQLITE.'--mediumText'][2] = ColumnType::text;
    $dataset[RL_SQLITE.'--smallInteger'][2] = ColumnType::integer;
    $dataset[RL_SQLITE.'--timestamp'][2] = ColumnType::dateTime;
    $dataset[RL_SQLITE.'--tinyInteger'][2] = ColumnType::integer;
    $dataset[RL_SQLITE.'--tinyText'][2] = ColumnType::text;
    $dataset[RL_SQLITE.'--uuid'][2] = ColumnType::string;
    $dataset[RL_SQLITE.'--ulid'][2] = ColumnType::string;
    $dataset[RL_SQLITE.'--year'][2] = ColumnType::integer;
    if (isLaravel10()) {
        // 11.x and 10.x have different mappings for float and double
        // https://github.com/laravel/framework/commit/4c1aa683ee1e003c731d6f9d1240b3b143e92382
        $dataset[RL_SQLITE.'--double'][2] = ColumnType::float;
    }

    /**
     * MySQL
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/data-types.html
     * @see vendor/laravel/framework/src/Illuminate/Database/Schema/Grammars/MySqlGrammar.php
     */
    $dataset[RL_MYSQL_8.'--boolean'][2] = ColumnType::tinyInteger;
    $dataset[RL_MYSQL_8.'--float'][2] = ColumnType::double;
    $dataset[RL_MYSQL_8.'--jsonb'][2] = ColumnType::json;
    $dataset[RL_MYSQL_8.'--uuid'][2] = ColumnType::char;
    $dataset[RL_MYSQL_8.'--ulid'][2] = ColumnType::char;

    /**
     * MariaDB
     *
     * @see https://mariadb.com/kb/en/data-types/
     * @see vendor/laravel/framework/src/Illuminate/Database/Schema/Grammars/MariaDbGrammar.php
     */
    $dataset[RL_MARIADB_10.'--boolean'][2] = ColumnType::tinyInteger;
    $dataset[RL_MARIADB_10.'--float'][2] = ColumnType::double;
    $dataset[RL_MARIADB_10.'--json'][2] = ColumnType::longText;
    $dataset[RL_MARIADB_10.'--jsonb'][2] = ColumnType::longText;
    $dataset[RL_MARIADB_10.'--uuid'][2] = ColumnType::char; // TODO Latest MariaDB should support `uuid`
    $dataset[RL_MARIADB_10.'--ulid'][2] = ColumnType::char;

    /**
     * Postgres
     *
     * @see https://www.postgresql.org/docs/14/datatype.html
     * @see vendor/laravel/framework/src/Illuminate/Database/Schema/Grammars/PostgresGrammar.php
     */
    $dataset[RL_PGSQL_14.'--dateTime'][2] = ColumnType::timestamp;
    $dataset[RL_PGSQL_14.'--float'][2] = ColumnType::double;
    $dataset[RL_PGSQL_14.'--longText'][2] = ColumnType::text;
    $dataset[RL_PGSQL_14.'--mediumInteger'][2] = ColumnType::integer;
    $dataset[RL_PGSQL_14.'--mediumText'][2] = ColumnType::text;
    $dataset[RL_PGSQL_14.'--tinyInteger'][2] = ColumnType::smallInteger;
    $dataset[RL_PGSQL_14.'--tinyText'][2] = ColumnType::string;
    $dataset[RL_PGSQL_14.'--ulid'][2] = ColumnType::char;
    $dataset[RL_PGSQL_14.'--year'][2] = ColumnType::integer;

    return $dataset;
});
