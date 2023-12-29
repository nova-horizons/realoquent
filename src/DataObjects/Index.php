<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Illuminate\Database\Schema\Blueprint;
use NovaHorizons\Realoquent\Enums\IndexType;
use NovaHorizons\Realoquent\RealoquentHelpers;
use NovaHorizons\Realoquent\Traits\Comparable;

class Index
{
    use Comparable;

    public function __construct(
        /** @readonly */
        public string $name,
        /** @readonly */
        public string $tableName,
        /** @readonly */
        public IndexType $type,
        /**
         * @var string[]
         *
         * @readonly
         */
        public array $indexColumns,

        /** @readonly */
        public bool $isSingleColumn = false,
        /** @readonly */
        public ?string $realoquentId = null,
    ) {
    }

    public static function fromDBAL(\Doctrine\DBAL\Schema\Index $dbalIndex, string $tableName): self
    {
        return new self(
            name: $dbalIndex->getName(),
            tableName: $tableName,
            type: IndexType::fromDBAL($dbalIndex),
            indexColumns: $dbalIndex->getColumns(),
            isSingleColumn: count($dbalIndex->getColumns()) === 1,
            realoquentId: RealoquentHelpers::newId()
        );
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public static function fromSchemaArray(string $name, array $schema, string $tableName): self
    {
        if (! isset($schema['indexColumns'])) {
            throw new \InvalidArgumentException("Invalid schema: Index ({$tableName}.{$name}) must have indexColumns");
        }

        return new self(
            name: $name,
            tableName: $tableName,
            type: $schema['type'],
            indexColumns: $schema['indexColumns'],
            realoquentId: $schema['realoquentId'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toSchemaArray(): ?array
    {
        $schema = [
            'type' => 'IndexType::'.$this->type->value,
            'indexColumns' => $this->getIndexColumns(),
        ];

        $schema['realoquentId'] = $this->realoquentId ?: RealoquentHelpers::newId(); // Add last to keep at end

        return $schema;
    }

    public static function newInferredIndex(IndexType $indexType, Column $column): self
    {
        return new Index(
            name: self::generateIndexName($column->tableName, $indexType->value, [$column->name]),
            tableName: $column->tableName,
            type: $indexType,
            indexColumns: [$column->name],
            isSingleColumn: true,
            realoquentId: $column->tableName.'_'.$column->name.'_'.$indexType->value,
        );
    }

    /**
     * @param  string[]  $columns
     *
     * @see Blueprint::createIndexName()
     */
    private static function generateIndexName(string $table, string $type, array $columns): string
    {
        $index = strtolower($table.'_'.implode('_', $columns).'_'.$type);

        return str_replace(['-', '.'], '_', $index);
    }

    /**
     * @return string[]
     */
    public function getIndexColumns(): array
    {
        return $this->indexColumns;
    }
}
