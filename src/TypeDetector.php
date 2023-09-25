<?php

namespace NovaHorizons\Realoquent;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NovaHorizons\Realoquent\Enums\ColumnType;

class TypeDetector
{
    /**
     * DBAL is fairly broad in how it resolves some of the types
     * Ex: TIMESTAMP and DATETIME are both DateTimeType
     *
     * @var array<class-string<Type>>
     */
    private static $controversialTypes = [
        DateTimeType::class,
        FloatType::class,
        JsonType::class,
        IntegerType::class,
        DateType::class,
        BooleanType::class,
        StringType::class,
        TextType::class,
    ];

    public static function fromDBAL(Column $dbalColumn, string $tableName): ColumnType
    {
        if (! in_array(get_class($dbalColumn->getType()), self::$controversialTypes)) {
            return ColumnType::fromDBAL($dbalColumn->getType());
        }

        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => self::fromMySQL($dbalColumn, $tableName),
            'pgsql' => self::fromPostgreSQL($dbalColumn, $tableName),
            default => ColumnType::fromDBAL($dbalColumn->getType()),
        };
    }

    private static function fromMySQL(Column $dbalColumn, string $tableName): ColumnType
    {
        $info = DB::selectOne(
            'SHOW COLUMNS FROM `'.$tableName.'` WHERE field = ?',
            [$dbalColumn->getName()]
        );
        $type = $info->Type;
        $type = Str::before($type, '(');

        return match ($type) {
            'char' => ColumnType::char,
            'double' => ColumnType::double,
            'longtext' => ColumnType::longText,
            'mediumint' => ColumnType::mediumInteger,
            'mediumtext' => ColumnType::mediumText,
            'timestamp' => ColumnType::timestamp,
            'tinytext' => ColumnType::tinyText,
            'tinyint' => ColumnType::tinyInteger,
            'year' => ColumnType::year,
            default => ColumnType::fromDBAL($dbalColumn->getType()),
        };
    }

    private static function fromPostgreSQL(Column $dbalColumn, string $tableName): ColumnType
    {
        $info = DB::selectOne(
            'SELECT * FROM information_schema.columns WHERE table_name = ? AND column_name = ?',
            [$tableName, $dbalColumn->getName()]
        );
        $type = $info->data_type;

        return match ($type) {
            'character' => ColumnType::char,
            'double precision' => ColumnType::double,
            'jsonb' => ColumnType::jsonb,
            'timestamp without time zone' => ColumnType::timestamp,
            default => ColumnType::fromDBAL($dbalColumn->getType()),
        };
    }

    //    private static function fromSQLite(\Doctrine\DBAL\Schema\Column $dbalColumn, string $tableName): ColumnType
    //    {
    //        $info = DB::select('PRAGMA table_info(' . $tableName . ')');
    //        $type = collect($info)->filter(fn($col) => $col->name === $dbalColumn->getName())->first();
    //
    //        return ColumnType::fromDBAL($dbalColumn->getType());
    //    }
}
