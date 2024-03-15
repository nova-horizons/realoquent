<?php

namespace NovaHorizons\Realoquent\Enums;

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @see https://laravel.com/docs/10.x/migrations#available-column-types Documentation on available types
 * @see \Illuminate\Database\Schema\Blueprint for implementation of each of the migration functions
 * @see \Illuminate\Database\Schema\Grammars for how each function is represented in database (see "type*" functions)
 */
enum ColumnType: string
{
    /*
     * Realoquent name => Laravel migration function name
     */
    case bigIncrements = 'bigIncrements'; // UNSIGNED BIGINT AUTO_INCREMENT
    case bigInteger = 'bigInteger'; // BIGINT
    case binary = 'binary'; // BLOB
    case boolean = 'boolean';
    case char = 'char';
    case dateTimeTz = 'dateTimeTz';
    case dateTime = 'dateTime';
    case date = 'date';
    case decimal = 'decimal';
    case double = 'double';
    case enum = 'enum';
    case float = 'float';
    case foreignId = 'foreignId'; // UNSIGNED BIGINT
    case foreignIdFor = 'foreignIdFor'; // UNSIGNED BIGINT
    case foreignUlid = 'foreignUlid'; // CHAR(26)
    case foreignUuid = 'foreignUuid';
    // TODO Breaking pgsql tests case geometryCollection = 'geometryCollection';
    case geometry = 'geometry';
    case id = 'id'; // UNSIGNED BIGINT AUTO_INCREMENT (same as bigIncrements)
    case increments = 'increments'; // UNSIGNED INTEGER AUTO_INCREMENT
    case integer = 'integer';
    case integerIncrements = 'integerIncrements';
    case ipAddress = 'ipAddress'; // VARCHAR(45)
    case json = 'json';
    case jsonb = 'jsonb';
    case lineString = 'lineString';
    case longText = 'longText';
    // TODO Breaking pgsql tests case macAddress = 'macAddress'; // VARCHAR(17)
    case mediumIncrements = 'mediumIncrements'; // UNSIGNED MEDIUMINT AUTO_INCREMENT
    case mediumInteger = 'mediumInteger';
    case mediumText = 'mediumText';
    // case morphs = 'morphs';
    case multiLineString = 'multiLineString';
    case multiPoint = 'multiPoint';
    case multiPolygon = 'multiPolygon';
    case multiPolygonZ = 'multiPolygonZ';
    // case nullableMorphs = 'nullableMorphs';
    // case nullableTimestamps = 'nullableTimestamps';
    // case nullableUlidMorphs = 'nullableUlidMorphs';
    // case nullableUuidMorphs = 'nullableUuidMorphs';
    case point = 'point';
    case polygon = 'polygon';
    case rememberToken = 'rememberToken'; // VARCHAR(100)
    case set = 'set';
    case smallIncrements = 'smallIncrements'; // UNSIGNED SMALLINT AUTO_INCREMENT
    case smallInteger = 'smallInteger';
    case softDeletesDatetime = 'softDeletesDatetime';
    case softDeletesTz = 'softDeletesTz';
    case softDeletes = 'softDeletes';
    case string = 'string'; // VARCHAR
    case text = 'text';
    case timeTz = 'timeTz';
    case time = 'time';
    case timestampTz = 'timestampTz';
    case timestamp = 'timestamp';
    // case timestampsTz = 'timestampsTz';
    // case timestamps = 'timestamps';
    case tinyIncrements = 'tinyIncrements'; // UNSIGNED TINYINT AUTO_INCREMENT
    case tinyInteger = 'tinyInteger';
    case tinyText = 'tinyText';
    case unsignedBigInteger = 'unsignedBigInteger';
    case unsignedDecimal = 'unsignedDecimal';
    case unsignedDouble = 'unsignedDouble';
    case unsignedFloat = 'unsignedFloat';
    case unsignedInteger = 'unsignedInteger';
    case unsignedMediumInteger = 'unsignedMediumInteger';
    case unsignedSmallInteger = 'unsignedSmallInteger';
    case unsignedTinyInteger = 'unsignedTinyInteger';
    // case ulidMorphs = 'ulidMorphs';
    // case uuidMorphs = 'uuidMorphs';
    case ulid = 'ulid'; // CHAR(26)
    case uuid = 'uuid';
    case year = 'year';

    /**
     * Get appropriate Eloquent Cast for column
     *
     * @see https://laravel.com/docs/10.x/eloquent-mutators#attribute-casting Cast documentation
     * @see \Illuminate\Database\Eloquent\Concerns\HasAttributes::castAttribute() for cast logic
     */
    public function getCast(): ?string
    {
        return match ($this) {
            self::bigIncrements => 'integer',
            self::bigInteger => 'integer',
            self::binary => null,
            self::boolean => 'boolean',
            self::char => 'string',
            self::dateTimeTz => 'datetime',
            self::dateTime => 'datetime',
            self::date => 'date',
            self::decimal => null,
            self::double => null,
            self::enum => null,
            self::float => 'float',
            self::foreignId => 'integer',
            self::foreignIdFor => null,
            self::foreignUlid => 'string',
            self::foreignUuid => 'string',
            // TODO Breaking pgsql tests self::geometryCollection => null,
            self::geometry => null,
            self::id => 'integer',
            self::increments => 'integer',
            self::integerIncrements => 'integer',
            self::integer => 'integer',
            self::ipAddress => 'string',
            self::json => null,
            self::jsonb => null,
            self::lineString => 'string',
            self::longText => 'string',
            // TODO Breaking pgsql tests self::macAddress => 'string',
            self::mediumIncrements => 'integer',
            self::mediumInteger => 'integer',
            self::mediumText => 'string',
            self::multiLineString => 'string',
            self::multiPoint => null,
            self::multiPolygon => null,
            self::multiPolygonZ => null,
            self::point => null,
            self::polygon => null,
            self::rememberToken => 'string',
            self::set => null,
            self::smallIncrements => 'integer',
            self::smallInteger => 'integer',
            self::softDeletesDatetime => null,
            self::softDeletesTz => null,
            self::softDeletes => null,
            self::string => 'string',
            self::text => 'string',
            self::timeTz => null,
            self::time => null,
            self::timestampTz => 'datetime',
            self::timestamp => 'datetime',
            self::tinyIncrements => 'integer',
            self::tinyInteger => 'integer',
            self::tinyText => 'string',
            self::unsignedBigInteger => 'integer',
            self::unsignedDecimal => 'float',
            self::unsignedDouble => 'float',
            self::unsignedFloat => 'float',
            self::unsignedInteger => 'integer',
            self::unsignedMediumInteger => 'integer',
            self::unsignedSmallInteger => 'integer',
            self::unsignedTinyInteger => 'integer',
            self::ulid => 'string',
            self::uuid => 'string',
            self::year => 'string',
            default => throw new \RuntimeException('Default cast not implemented for type: '.$this->value),
        };
    }

    public function supportsLength(): bool
    {
        return in_array($this, [
            self::char,
            self::string,
        ]);
    }

    public function supportsPrecision(): bool
    {
        return in_array($this, [
            self::dateTime,
            self::dateTimeTz,
            self::decimal,
            self::double,
            self::float,
            self::softDeletes,
            self::softDeletesDatetime,
            self::softDeletesTz,
            self::time,
            self::timestamp,
            self::timestampTz,
            self::timeTz,
            self::unsignedDecimal,
            self::unsignedDouble,
            self::unsignedFloat,
        ]);
    }

    public function supportsScale(): bool
    {
        return in_array($this, [
            self::decimal,
            self::double,
            self::float,
            self::unsignedDecimal,
            self::unsignedDouble,
            self::unsignedFloat,
        ]);
    }

    public function isUnsigned(): bool
    {
        return in_array($this, [
            self::bigIncrements,
            self::foreignId,
            self::id,
            self::increments,
            self::integerIncrements,
            self::mediumIncrements,
            self::smallIncrements,
            self::tinyIncrements,
            self::unsignedBigInteger,
            self::unsignedDecimal,
            self::unsignedDouble,
            self::unsignedFloat,
            self::unsignedInteger,
            self::unsignedMediumInteger,
            self::unsignedSmallInteger,
            self::unsignedTinyInteger,

        ]);
    }

    public function isAutoIncrement(): bool
    {
        return in_array($this, [
            self::bigIncrements,
            self::id,
            self::increments,
            self::integerIncrements,
            self::mediumIncrements,
            self::smallIncrements,
            self::tinyIncrements,
        ]);
    }

    public function getDefaultLength(): ?int
    {
        if (! $this->supportsLength()) {
            return null;
        }

        return match ($this) {
            self::char => Builder::$defaultStringLength,
            self::string => Builder::$defaultStringLength,
            default => throw new \RuntimeException('Default length not implemented for type: '.$this->value),
        };
    }

    public function getDefaultPrecision(): ?int
    {
        if (! $this->supportsPrecision()) {
            return null;
        }

        $isPgsql = DB::connection()->getDriverName() === 'pgsql';
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        return match ($this) {
            self::dateTime => 10,
            self::dateTimeTz => 10,
            self::decimal => $isSqlite ? 10 : 8,
            self::double => 10,
            self::float => $isPgsql || $isSqlite ? 10 : 8,
            self::softDeletesTz => 10,
            self::softDeletes => 10,
            self::softDeletesDatetime => 10,
            self::timeTz => 10,
            self::time => 10,
            self::timestamp => 10,
            self::timestampTz => 10,
            self::unsignedDecimal => $isSqlite ? 10 : 8,
            self::unsignedDouble => 10,
            self::unsignedFloat => $isPgsql || $isSqlite ? 10 : 8,
            default => throw new \RuntimeException('Default precision not implemented for type: '.$this->value),
        };
    }

    public function getDefaultScale(): ?int
    {
        if (! $this->supportsScale()) {
            return null;
        }

        $isPgsql = DB::connection()->getDriverName() === 'pgsql';
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        return match ($this) {
            self::decimal => $isSqlite ? 0 : 2,
            self::double => 0,
            self::float => $isPgsql || $isSqlite ? 0 : 2,
            self::unsignedDecimal => $isSqlite ? 0 : 2,
            self::unsignedDouble => 0,
            self::unsignedFloat => $isPgsql || $isSqlite ? 0 : 2,
            default => throw new \RuntimeException('Default scale not implemented for type: '.$this->value),
        };
    }

    /**
     * Laravel has several shorthand types that can be used in migrations.
     * To minimize noise in schema.php, we can detect when to use this shorthand types
     *    ex. an unsigned & auto-incrementing `bigInteger` can change to `bigIncrements`
     */
    public static function determineOptimalType(self $type, bool $unsigned, bool $autoIncrements): self
    {
        if ($autoIncrements) {
            return match ($type) {
                self::bigInteger => self::bigIncrements,
                self::integer => self::integerIncrements,
                self::mediumInteger => self::mediumIncrements,
                self::smallInteger => self::smallIncrements,
                self::tinyInteger => self::tinyIncrements,
                default => $type,
            };
        }

        if ($unsigned) {
            return match ($type) {
                self::bigInteger => self::unsignedBigInteger,
                self::decimal => self::unsignedDecimal,
                self::double => self::unsignedDouble,
                self::float => self::unsignedFloat,
                self::integer => self::unsignedInteger,
                self::mediumInteger => self::unsignedMediumInteger,
                self::smallInteger => self::unsignedSmallInteger,
                self::tinyInteger => self::unsignedTinyInteger,
                default => $type,
            };
        }

        return $type;
    }

    public function getMigrationFunction(): string
    {
        return $this->value;
    }
}
