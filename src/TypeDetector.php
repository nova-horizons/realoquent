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
            'datetime' => ColumnType::dateTime,
            'date' => ColumnType::date,
            'char' => ColumnType::char,
            'decimal' => ColumnType::decimal,
            'int' => ColumnType::integer,
            'json' => ColumnType::json,
            'double' => ColumnType::double,
            'longtext' => ColumnType::longText,
            'mediumint' => ColumnType::mediumInteger,
            'varchar' => ColumnType::string,
            'time' => ColumnType::time,
            'mediumtext' => ColumnType::mediumText,
            'smallint' => ColumnType::smallInteger,
            'timestamp' => ColumnType::timestamp,
            'tinytext' => ColumnType::tinyText,
            'tinyint' => ColumnType::tinyInteger,
            'year' => ColumnType::year,
            default => throw new RuntimeException('Unknown DB type: '.$type),
        };
    }

    private static function fromPostgreSQL(string $type): ColumnType
    {
        $baseType = Str::before($type, '(');

        return match ($baseType) {
            'character' => ColumnType::char,
            'double precision' => ColumnType::double,
            'jsonb' => ColumnType::jsonb,
            'bigint' => ColumnType::bigInteger,
            'bytea' => ColumnType::binary,
            'boolean' => ColumnType::boolean,
            'date' => ColumnType::date,
            'numeric' => ColumnType::decimal,
            'integer' => ColumnType::integer,
            'json' => ColumnType::json,
            'text' => ColumnType::text,
            'smallint' => ColumnType::smallInteger,
            'character varying' => ColumnType::string,
            'time' => ColumnType::time,
            'uuid' => ColumnType::uuid,
            'timestamp' => ColumnType::timestamp,
            default => throw new RuntimeException('Unknown DB type: '.$type),
        };
    }

    private static function fromSqlite(mixed $type): ColumnType
    {
        $baseType = Str::before($type, '(');

        return match ($baseType) {
            'integer' => ColumnType::integer,
            'blob' => ColumnType::binary,
            'varchar' => ColumnType::string,
            'datetime' => ColumnType::dateTime,
            'date' => ColumnType::date,
            'numeric' => ColumnType::decimal,
            'float' => ColumnType::float,
            'text' => ColumnType::text,
            'time' => ColumnType::time,
            'tinyint' => ColumnType::tinyInteger,
            default => throw new RuntimeException('Unknown DB type: '.$type),
        };
    }
}
