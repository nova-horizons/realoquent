<?php

namespace NovaHorizons\Realoquent;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NovaHorizons\Realoquent\Enums\ColumnType;
use RuntimeException;

class TypeDetector
{
    /**
     * @param  array<string, mixed>  $dbColumn
     */
    public static function fromDB(array $dbColumn): ColumnType
    {
        $type = $dbColumn['type'];

        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => self::fromMySQL($type),
            'pgsql' => self::fromPostgreSQL($type),
            'sqlite' => self::fromSqlite($type),
            default => throw new RuntimeException('Unsupported DB driver: '.DB::connection()->getDriverName()),
        };
    }

    /**
     * @param  array<string, mixed>  $dbColumn
     * @return array{length: int|null, precision: int|null, scale: int|null} $info
     */
    public static function getInfo(array $dbColumn): array
    {
        $matches = [];
        $pattern = '/[a-z+]\((\d+)(?:,(\d+))?\)/i';

        preg_match($pattern, $dbColumn['type'], $matches);

        $precision = isset($matches[1]) ? intval($matches[1]) : null;
        $scale = isset($matches[2]) ? intval($matches[2]) : null;
        if ($precision && ! $scale) {
            $length = $precision;
            $precision = null;
        } else {
            $length = null;
        }

        return [
            'length' => $length,
            'precision' => $precision,
            'scale' => $scale,
        ];
    }

    private static function fromMySQL(string $type): ColumnType
    {
        $baseType = Str::before($type, '('); // varchar(255) -> varchar
        $baseType = Str::before($baseType, ' '); // integer unsigned -> integer

        return match ($baseType) {
            'bigint' => ColumnType::bigInteger,
            'blob' => ColumnType::binary,
            'char' => ColumnType::char,
            'date' => ColumnType::date,
            'datetime' => ColumnType::dateTime,
            'decimal' => ColumnType::decimal,
            'double' => ColumnType::double,
            'int' => ColumnType::integer,
            'json' => ColumnType::json,
            'longtext' => ColumnType::longText,
            'mediumint' => ColumnType::mediumInteger,
            'mediumtext' => ColumnType::mediumText,
            'smallint' => ColumnType::smallInteger,
            'time' => ColumnType::time,
            'timestamp' => ColumnType::timestamp,
            'tinyint' => ColumnType::tinyInteger,
            'tinytext' => ColumnType::tinyText,
            'varchar' => ColumnType::string,
            'year' => ColumnType::year,
            default => throw new RuntimeException('Unknown DB type: '.$type),
        };
    }

    private static function fromPostgreSQL(string $type): ColumnType
    {
        $baseType = Str::before($type, '(');

        return match ($baseType) {
            'bigint' => ColumnType::bigInteger,
            'boolean' => ColumnType::boolean,
            'bytea' => ColumnType::binary,
            'character varying' => ColumnType::string,
            'character' => ColumnType::char,
            'date' => ColumnType::date,
            'double precision' => ColumnType::double,
            'integer' => ColumnType::integer,
            'json' => ColumnType::json,
            'jsonb' => ColumnType::jsonb,
            'numeric' => ColumnType::decimal,
            'smallint' => ColumnType::smallInteger,
            'text' => ColumnType::text,
            'time' => ColumnType::time,
            'timestamp' => ColumnType::timestamp,
            'uuid' => ColumnType::uuid,
            default => throw new RuntimeException('Unknown DB type: '.$type),
        };
    }

    private static function fromSqlite(mixed $type): ColumnType
    {
        $baseType = Str::before($type, '(');

        return match ($baseType) {
            'blob' => ColumnType::binary,
            'date' => ColumnType::date,
            'datetime' => ColumnType::dateTime,
            'double' => ColumnType::double,
            'float' => ColumnType::float,
            'integer' => ColumnType::integer,
            'numeric' => ColumnType::decimal,
            'text' => ColumnType::text,
            'time' => ColumnType::time,
            'tinyint' => ColumnType::tinyInteger,
            'varchar' => ColumnType::string,
            default => throw new RuntimeException('Unknown DB type: '.$type),
        };
    }
}
