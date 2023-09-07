<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Illuminate\Support\Collection;

class Schema
{
    /** @var Table[] */
    protected array $tables = [];

    /** @var Collection<string, string> */
    protected Collection $orphanModels;

    public function __construct()
    {
        $this->orphanModels = collect();
    }

    /**
     * @param  array<string, mixed>  $schemaArray
     */
    public static function fromSchemaArray(array $schemaArray): self
    {
        $schema = new self();
        foreach ($schemaArray as $tableName => $tableArray) {
            $table = Table::fromSchemaArray($tableName, $tableArray);
            $schema->addTable($table);
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchemaArray(): array
    {
        $schemaArray = [];
        foreach ($this->tables as $table) {
            $schemaArray[$table->name] = $table->toSchemaArray();
        }

        return $schemaArray;
    }

    public function addTable(Table $realTable): void
    {
        $this->tables[$realTable->name] = $realTable;
    }

    /**
     * @return array<string, Table>
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * @param  Collection<string, string>  $models
     */
    public function setOrphanModels(Collection $models): void
    {
        $this->orphanModels = $models;
    }

    /**
     * @return Collection<string, string>
     */
    public function getOrphanModels(): Collection
    {
        return $this->orphanModels;
    }
}
