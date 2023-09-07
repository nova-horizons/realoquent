<?php

namespace NovaHorizons\Realoquent;

use NovaHorizons\Realoquent\DataObjects\Column;
use NovaHorizons\Realoquent\DataObjects\Index;
use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\DataObjects\SchemaChanges;
use NovaHorizons\Realoquent\DataObjects\Table;
use NovaHorizons\Realoquent\Exceptions\DuplicateIdException;

class SchemaDiffer
{
    /**
     * @var array<string, Table|Column|Index>
     */
    protected array $currentSchemaByIds = [];

    public function __construct(
        protected readonly Schema $currentSchema,
        protected readonly Schema $newSchema,
    ) {
    }

    /**
     * @throws DuplicateIdException
     */
    public function getSchemaChanges(): SchemaChanges
    {
        $diffs = [];
        $this->validateAndReindexCurrentSchema(schema: $this->newSchema, allowEmptyIds: true);
        $this->currentSchemaByIds = $this->validateAndReindexCurrentSchema(schema: $this->currentSchema, allowEmptyIds: false);

        $renamedTables = [];

        // TODO De-dupe this code
        foreach ($this->newSchema->getTables() as $newTable) {
            $id = $newTable->realoquentId;
            $oldTable = $this->currentSchemaByIds[$id] ?? null;
            $diffs = array_merge_recursive($diffs, $newTable->compare($oldTable));
            isset($diffs['table_renamed'][$newTable->name]) && $renamedTables[] = $oldTable->name;
            unset($this->currentSchemaByIds[$id]);

            if (! $oldTable) {
                continue;
            }

            foreach ($newTable->getColumns() as $newColumn) {
                $colId = $newColumn->realoquentId;
                $oldColumn = $this->currentSchemaByIds[$colId] ?? null;
                $diffs = array_merge_recursive($diffs, $newColumn->compare($oldColumn));
                unset($this->currentSchemaByIds[$colId]);
            }

            foreach ($newTable->getIndexes() as $newIndex) {
                if (isset($diffs['table_renamed'][$newTable->name]) && $newIndex->isSingleColumn) {
                    // Skip single column indexes on renamed tables
                    // Names are generated from the table name, so they will mismatch and show as new
                    continue;
                }
                $indexId = $newIndex->realoquentId;
                $oldIndex = $this->currentSchemaByIds[$indexId] ?? null;
                $diffs = array_merge_recursive($diffs, $newIndex->compare($oldIndex));
                unset($this->currentSchemaByIds[$indexId]);
            }
        }

        $removedItems = collect($this->currentSchemaByIds);

        $removedTables = collect($removedItems)->filter(fn ($item) => $item instanceof Table);
        /** @var Table $removedTable */
        foreach ($removedTables as $removedTable) {
            // Remove Columns/Indexes from our remaining item list
            $childIds = collect($removedTable->getColumns())
                ->pluck('realoquentId')
                ->merge(
                    collect($removedTable->getIndexes())
                        ->pluck('realoquentId')
                );
            foreach ($childIds as $childId) {
                unset($removedItems[$childId]);
            }
            $diffs['table_removed'][$removedTable->name] = $removedTable;
            unset($removedItems[$removedTable->realoquentId]);
        }

        /** @var Column|Index $removedItem */
        foreach ($removedItems as $removedItem) {
            // Skip inferred indexes on rename (ID is based on table name, so they show as removed incorrectly on rename)
            if (in_array($removedItem->tableName, $renamedTables) && $removedItem instanceof Index && $removedItem->isSingleColumn) {
                continue;
            }
            $type = strtolower(class_basename($removedItem));
            $diffs[$type.'_removed'][$removedItem->tableName.'.'.$removedItem->name] = $removedItem;
        }

        return new SchemaChanges($diffs);
    }

    /**
     * @return array<string, Table|Column|Index>
     *
     * @throws DuplicateIdException
     * @throws \Throwable
     */
    protected function validateAndReindexCurrentSchema(Schema $schema, bool $allowEmptyIds): array
    {
        $errors = [];
        $schemaByIds = [];

        // TODO De-dupe this code

        foreach ($schema->getTables() as $tableName => $table) {
            $tableId = $table->realoquentId;
            if (! $allowEmptyIds && ! $tableId) {
                $errors[] = 'Table '.$tableName.' has no realoquentId';
            }

            throw_if(isset($schemaByIds[$tableId]) && $tableId, new DuplicateIdException('table', $tableName, $tableId));
            $schemaByIds[$tableId] = $table;

            foreach ($table->getColumns() as $columnName => $column) {
                $columnId = $column->realoquentId;
                if (! $allowEmptyIds && ! $columnId) {
                    $errors[] = 'Column '.$tableName.'.'.$columnName.' has no realoquentId';
                }
                throw_if(isset($schemaByIds[$columnId]) && $columnId, new DuplicateIdException('column', $columnName, $columnId));
                $schemaByIds[$columnId] = $column;
            }

            foreach ($table->getIndexes() as $indexName => $index) {
                $indexId = $index->realoquentId;
                if (! $allowEmptyIds && ! $indexId) {
                    $errors[] = 'Index '.$tableName.'.'.$indexName.' has no realoquentId';
                }
                throw_if(isset($schemaByIds[$indexId]) && $indexId, new DuplicateIdException('index', $indexName, $indexId));
                $schemaByIds[$indexId] = $index;
            }
        }

        if (! empty($errors)) {
            throw new \RuntimeException('Schema snapshot is invalid: '.implode("\n", $errors));
        }

        return $schemaByIds;
    }
}
