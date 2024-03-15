<?php

namespace NovaHorizons\Realoquent;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NovaHorizons\Realoquent\Enums\ColumnType;

class DatabaseAnalyzer
{
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

            $default = $dbColumn['default'];
            if (str_starts_with($default, "'") && str_ends_with($default, "'")) {
                $default = trim($default, "'");
            }
            if (DB::connection()->getDriverName() === 'sqlite') {
                if (strlen($default) === 0 && $default === false) {
                    $default = null;
                }
            }

            if (DB::connection()->getDriverName() === 'pgsql') {
                if ($dbColumn['realoquent_type'] === ColumnType::boolean) {
                    $default = (strtolower($default) === 'true');
                } elseif (str_ends_with($default, '::character varying')) {
                    $default = Str::beforeLast($default, '::character varying');
                }
                if (str_starts_with($default, "'") && str_ends_with($default, "'")) {
                    $default = trim($default, "'");
                }
            }
            $dbColumn['default'] = $default;
        }

        return $dbColumns;
    }
}
