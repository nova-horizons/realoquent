<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Illuminate\Support\Carbon;
use NovaHorizons\Realoquent\Enums\ColumnType;
use NovaHorizons\Realoquent\Enums\RelationshipType;
use NovaHorizons\Realoquent\RealoquentHelpers;
use NovaHorizons\Realoquent\Traits\Comparable;

class Column
{
    use Comparable;

    public function __construct(
        /** @readonly */
        public string $name,
        /** @readonly */
        public string $tableName,
        /** @readonly */
        public ColumnType $type,
        /** @readonly */
        public ?int $length = null,
        /** @readonly */
        public ?int $precision = null,
        /** @readonly */
        public ?int $scale = null,
        /** @readonly */
        public bool $unsigned = false,
        /** @readonly */
        public bool $nullable = false,
        /** @readonly */
        public ?string $default = null,
        /** @readonly */
        public bool $autoIncrement = false,
        /** @readonly */
        public bool $fillable = false,
        /** @readonly */
        public bool $guarded = true,
        /** @readonly */
        public ?string $cast = null,
        /** @readonly */
        public ?Relation $relation = null,
        /**
         * @var string[]
         *
         * @readonly
         */
        public array $validation = [],
        /**
         * @var string[]
         *
         * @readonly
         */
        public array $validationGroups = [],
        /** @readonly */
        public ?string $realoquentId = null,
    ) {
        $this->reconcileTypeAndProperties();
    }

    public static function fromDBAL(\Doctrine\DBAL\Schema\Column $dbalColumn, string $tableName): self
    {
        $type = ColumnType::fromDBAL($dbalColumn->getType());

        return new self(
            name: $dbalColumn->getName(),
            tableName: $tableName,
            type: $type,
            length: $dbalColumn->getLength(),
            precision: $dbalColumn->getPrecision(),
            scale: $dbalColumn->getScale(),
            unsigned: $dbalColumn->getUnsigned(),
            nullable: ! $dbalColumn->getNotnull(),
            default: $dbalColumn->getDefault(),
            autoIncrement: $dbalColumn->getAutoincrement(),
            realoquentId: RealoquentHelpers::newId(),
        );
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public static function fromSchemaArray(string $name, array $schema, string $tableName): self
    {
        $schema['name'] = $name;
        $schema['tableName'] = $tableName;
        unset($schema['primary'], $schema['unique'], $schema['index']);

        return new self(...$schema);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchemaArray(): array
    {
        $schema = [];
        if (isset($this->relation)) {
            $schema['type'] = 'RelationshipType::'.$this->relation->type->value;
            $schema['relatedModel'] = $this->relation->relatedModel;
            $schema['relationName'] = $this->relation->relationName;
            $schema['localKey'] = $this->relation->localKey;
            $schema['foreignKey'] = $this->relation->foreignKey;
        } else {
            $schema['type'] = 'ColumnType::'.$this->type->value;
        }

        $this->autoIncrement && $schema['autoIncrement'] = $this->autoIncrement;
        $this->nullable && $schema['nullable'] = $this->nullable;
        $this->unsigned && $schema['unsigned'] = $this->unsigned;
        ! is_null($this->default) && $schema['default'] = $this->default;
        isset($this->length) && $schema['length'] = $this->length;
        isset($this->precision) && $schema['precision'] = $this->precision;
        isset($this->scale) && $schema['scale'] = $this->scale;
        isset($this->cast) && $schema['cast'] = $this->cast;

        if ($this->fillable) {
            $schema['fillable'] = $this->fillable;
        } else {
            $schema['guarded'] = $this->guarded;
        }

        ! empty($this->validation) && $this->fillable && $schema['validation'] = $this->validation;
        ! empty($this->validationGroups) && $this->fillable && $schema['validationGroups'] = $this->validationGroups;

        $schema['realoquentId'] = $this->realoquentId ?: RealoquentHelpers::newId(); // Add last to keep at end

        return $schema;
    }

    public function setFillable(bool $value): void
    {
        $this->fillable = $value;
    }

    public function setGuarded(bool $value): void
    {
        $this->guarded = $value;
    }

    public function setCast(string $value): void
    {
        $this->cast = $value;
    }

    /**
     * @param  string[]  $value
     */
    public function setValidation(array $value): void
    {
        $this->validation = array_unique(array_merge($this->validation, $value));
    }

    /**
     * @param  string[]  $value
     */
    public function setValidationGroups(array $value): void
    {
        $this->validationGroups = $value;
    }

    /**
     * Laravel has several shorthand types that can be used in migrations.
     * To minimize noise in schema.php, we can remove redundant properties based on the type
     *    ex. bigIncrements is unsigned & auto-incrementing; we don't need to separately list out those properties
     * Also remove properties that DBAL returns even if they aren't valid for the column type
     */
    private function reconcileTypeAndProperties(): void
    {
        $this->type = ColumnType::determineOptimalType(
            type: $this->type,
            unsigned: $this->unsigned,
            autoIncrements: $this->autoIncrement);

        if (! $this->type->supportsLength() || $this->length === $this->type->getDefaultLength()) {
            unset($this->length);
        }

        if (! $this->type->supportsPrecision() || $this->precision === $this->type->getDefaultPrecision()) {
            unset($this->precision);
        }

        if (! $this->type->supportsScale() || $this->scale === $this->type->getDefaultScale()) {
            unset($this->scale);
        }

        // If type indicates auto-increment, we can remove separate flag to minimize noise in schema
        if ($this->type->isAutoIncrement()) {
            $this->autoIncrement = false;
        }

        // If type indicates unsigned, we can remove separate flag to minimize noise in schema
        if ($this->type->isUnsigned()) {
            $this->unsigned = false;
        }
    }

    /**
     * Get PHP type for column to use in `@property` PHPDocs
     */
    public function getPhpType(): string
    {
        $cast = $this->type->getCast();

        $type = match ($cast) {
            'boolean' => 'boolean',
            'date' => Carbon::class,
            'datetime' => Carbon::class,
            'decimal' => 'float',
            'float' => 'float',
            'integer' => 'integer',
            'string' => 'string',
            'timestamp' => Carbon::class,
            null => 'mixed',
            default => throw new \RuntimeException('Unknown PHP Type for Cast: '.$cast),
        };

        if ($this->nullable) {
            $type = '?'.$type;
        }

        return $type;
    }

    /**
     * @see https://laravel.com/docs/10.x/validation#available-validation-rules
     *
     * @return string[]
     */
    public function generateDefaultValidation(bool $isUnique = false): array
    {
        $rules = [];

        if (! $this->nullable) {
            $rules[] = 'required';
        }

        if ($this->type->supportsLength()) {
            $rules[] = 'max:'.($this->length ?? $this->type->getDefaultLength());
        }

        if ($this->type->getCast() === 'integer') {
            $rules[] = 'integer';
        }

        if ($this->type->getCast() === 'float') {
            $rules[] = 'float';
        }

        if (in_array($this->type->getCast(), ['date', 'datetime', 'timestamp'])) {
            $rules[] = 'date';
        }

        if ($this->type->isUnsigned()) {
            $rules[] = 'min:0';
        }

        match ($this->type) {
            ColumnType::ipAddress => $rules[] = 'ip',
            // ColumnType::macAddress => $rules[] = 'mac_address',
            ColumnType::ulid => $rules[] = 'ulid',
            ColumnType::uuid => $rules[] = 'uuid',
            default => null,
        };

        if ($isUnique) {
            $rules[] = 'unique:'.$this->tableName;
        }

        return array_unique($rules);

    }

    public function setLocalRelationship(Relation $relation, Column $otherColumn): void
    {
        $this->type = $otherColumn->type;
        isset($otherColumn->length) && $this->length = $otherColumn->length;
        isset($otherColumn->precision) && $this->precision = $otherColumn->precision;
        isset($otherColumn->scale) && $this->scale = $otherColumn->scale;
        $this->unsigned = $otherColumn->unsigned;
        $this->relation = $relation;
    }

    public function setForeignRelationshipType(Relation $relation, Column $otherColumn): void
    {
        $this->type = $otherColumn->type;
        // TODO $this->relationshipType = $relation->type->getInverse();
    }
}
