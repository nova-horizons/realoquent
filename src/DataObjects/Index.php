<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Illuminate\Database\Schema\Blueprint;
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
        /**
         * @var string[]
         *
         * @readonly
         */
        public array $columns,
        /** @readonly */
        public bool $isPrimary = false,
        /** @readonly */
        public bool $isUnique = false,
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
            columns: $dbalIndex->getColumns(),
            isPrimary: $dbalIndex->isPrimary(),
            isUnique: $dbalIndex->isUnique(),
            isSingleColumn: count($dbalIndex->getColumns()) === 1,
            realoquentId: RealoquentHelpers::newId()
        );
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public static function fromSchemaArray(string $name, array $schema, string $tableName): self
    {
        return new self(
            name: $name,
            tableName: $tableName,
            columns: $schema['indexColumns'],
            isPrimary: $schema['isPrimary'] ?? false,
            isUnique: $schema['isUnique'] ?? false,
            realoquentId: $schema['realoquentId'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toSchemaArray(): ?array
    {
        $schema = [
            'indexColumns' => $this->getColumns(),
        ];

        $this->isPrimary && $schema['isPrimary'] = $this->isPrimary;
        $this->isUnique && $schema['isUnique'] = $this->isUnique;

        $schema['realoquentId'] = $this->realoquentId; // Add last to keep at end

        return $schema;
    }

    public static function newInferredPrimary(Column $column): self
    {
        return new Index(
            name: self::generateIndexName($column->tableName, 'primary', [$column->name]),
            tableName: $column->tableName,
            columns: [$column->name],
            isPrimary: true,
            isUnique: true,
            isSingleColumn: true,
            realoquentId: $column->tableName.'_primary',
        );
    }

    public static function newInferredUnique(Column $column): self
    {
        return new Index(
            name: self::generateIndexName($column->tableName, 'unique', [$column->name]),
            tableName: $column->tableName,
            columns: [$column->name],
            isPrimary: false,
            isUnique: true,
            isSingleColumn: true,
            realoquentId: $column->tableName.'_'.$column->name.'_unique',
        );
    }

    public static function newInferredIndex(Column $column): self
    {
        return new Index(
            name: self::generateIndexName($column->tableName, 'index', [$column->name]),
            tableName: $column->tableName,
            columns: [$column->name],
            isPrimary: false,
            isUnique: false,
            isSingleColumn: true,
            realoquentId: $column->tableName.'_'.$column->name.'_index',
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
    public function getColumns(): array
    {
        return $this->columns;
    }
}
