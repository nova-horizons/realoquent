<?php

namespace NovaHorizons\Realoquent;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NovaHorizons\Realoquent\Enums\ColumnType;

class DatabaseAnalyzer
{
    /**
     * @return array<int, string>
     */
    public static function getTables(): array
    {
        return DB::connection()->getSchemaBuilder()->getTableListing();
    }

    /**
     * @return array<int, array{name: string, type_name: string, type: string, collation: string|null, nullable: bool, default: mixed, auto_increment: bool, comment: string|null, length: int|null, precision: int|null, scale: int|null, unsigned: bool, realoquent_type: ColumnType}>
     */
    public static function getColumns(string $table): array
    {
        $dbColumns = DB::connection()->getSchemaBuilder()->getColumns($table);

        foreach ($dbColumns as &$dbColumn) {
            $dbColumn = array_merge($dbColumn, TypeDetector::getInfo($dbColumn));
            $dbColumn['realoquent_type'] = TypeDetector::fromDB($dbColumn);
            $dbColumn['unsigned'] = str_contains($dbColumn['type'], 'unsigned');
            $dbColumn['default'] = self::parseDefault($dbColumn['default'], $dbColumn['realoquent_type']);
        }

        return $dbColumns;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getIndexes(string $tableName): array
    {
        return DB::connection()->getSchemaBuilder()->getIndexes($tableName);
    }

    protected static function parseDefault(mixed $default, ColumnType $realoquentType): mixed
    {
        if (str_starts_with($default, "'") && str_ends_with($default, "'")) {
            $default = trim($default, "'");
        }
        if (self::isSqlite()) {
            if (strlen($default) === 0 || $default === false) {
                $default = null;
            }
        }

        if (self::isPostgres()) {
            if ($realoquentType === ColumnType::boolean) {
                $default = (strtolower($default) === 'true');
            } elseif (str_ends_with($default, '::character varying')) {
                $default = Str::beforeLast($default, '::character varying');
            }
            if (str_starts_with($default, "'") && str_ends_with($default, "'")) {
                $default = trim($default, "'");
            }
        }

        return $default;
    }

    public static function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    public static function isMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    public static function isPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    public static function isMariaDb(): bool
    {
        return DB::connection()->getDriverName() === 'mariadb';
    }
}
