<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Doctrine\DBAL\Schema\Table as DoctrineTable;
use NovaHorizons\Realoquent\Enums\IndexType;
use NovaHorizons\Realoquent\RealoquentHelpers;
use NovaHorizons\Realoquent\Traits\Comparable;

class Table
{
    use Comparable;

    /** @readonly */
    public readonly string $name;

    /** @readonly */
    public string|bool|null $model;

    /** @readonly */
    public string $primaryKey;

    /** @readonly */
    public string $keyType;

    /** @readonly */
    public bool $incrementing;

    /** @readonly */
    public ?string $realoquentId;

    /** @var array<string, Column> */
    protected array $columns = [];

    /** @var array<string, Index> */
    protected array $indexes = [];

    public function __construct(string $name, ?string $realoquentId = null)
    {
        $this->name = $name;
        $this->realoquentId = $realoquentId;
    }

    public function addColumn(Column $column): void
    {
        $this->columns[$column->name] = $column;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function addIndex(Index $index): void
    {
        $this->indexes[$index->name] = $index;

        // Sort Primary first, then alphabetical
        uasort($this->indexes, function ($a, $b) {
            /** @var Index $a */
            /** @var Index $b */
            if ($a->type === IndexType::primary) {
                return -1;
            }

            return $a->name <=> $b->name;
        });

        if ($index->type === IndexType::primary) {
            $this->primaryKey = $index->indexColumns[0];
            $this->keyType = $this->columns[$this->primaryKey]->type->getCast();
            $this->incrementing = $this->columns[$this->primaryKey]->type->isAutoIncrement();
        }

    }

    /**
     * @return Index[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function shouldHaveModel(): bool
    {
        return ($this->model ?? true) !== false;
    }

    /**
     * @return string[]
     */
    public function getFillableColumns(): array
    {
        $fillableColumns = [];
        foreach ($this->columns as $column) {
            if ($column->fillable) {
                $fillableColumns[] = $column->name;
            }
        }

        return $fillableColumns;
    }

    /**
     * @return string[]
     */
    public function getGuardedColumns(): array
    {
        $guardedColumns = [];
        foreach ($this->columns as $column) {
            if ($column->guarded) {
                $guardedColumns[] = $column->name;
            }
        }

        if (count($guardedColumns) === count($this->columns)) {
            return ['*'];
        }

        return $guardedColumns;
    }

    /**
     * @return array<string, string>
     */
    public function getCastColumns(): array
    {
        $castColumns = [];
        foreach ($this->columns as $column) {
            $cast = $column->cast ?: $column->type->getCast();
            if (! is_null($cast)) {
                $castColumns[$column->name] = $cast;
            }
        }

        return $castColumns;
    }

    /**
     * @return array<string, string[]>
     */
    public function getValidation(): array
    {
        $validation = [];
        foreach ($this->columns as $column) {
            if (empty($column->validation)) {
                $hasUniqueIndex = $this->doesColumnHaveUniqueIndex($column->name);
                $validation[$column->name] = $column->generateDefaultValidation(isUnique: $hasUniqueIndex);
            } else {
                $validation[$column->name] = $column->validation;
            }
        }

        return $validation;
    }

    /**
     * @return array<string, string[]>
     */
    public function getValidationGroups(): array
    {
        $groups = [];
        foreach ($this->columns as $column) {
            foreach ($column->validationGroups as $group) {
                if (! isset($groups[$group])) {
                    $groups[$group] = [];
                }
                $groups[$group][] = $column->name;
            }
        }

        return $groups;
    }

    public function setAndParseModel(string $model): void
    {
        $modelInfo = new ModelInfo($model);
        $this->model = $modelInfo->name;
        $this->primaryKey = $modelInfo->primaryKey;
        $this->keyType = $modelInfo->keyType;

        // Fillable
        foreach ($modelInfo->fillable as $field) {
            if ($field === '*') {
                foreach ($this->columns as $column) {
                    $column->setFillable(true);
                }
            } else {
                $this->columns[$field]->setFillable(true);
            }
        }

        // Guarded
        foreach ($modelInfo->guarded as $field) {
            if ($field === '*') {
                foreach ($this->columns as $column) {
                    if (isset($column->fillable) && $column->fillable === false) {
                        $column->setGuarded(true);
                    }
                }
            } else {
                $this->columns[$field]->setGuarded(true);
            }
        }

        // Casts
        foreach ($modelInfo->casts as $field => $cast) {
            if (! isset($this->columns[$field])) {
                continue;
            }
            if ($cast !== $this->columns[$field]->type->getCast()) {
                $this->columns[$field]->setCast($cast);
            }
        }

        // Validation
        if (! empty($modelInfo->validation)) {
            foreach ($modelInfo->validation as $field => $rules) {
                $this->columns[$field]->setValidation($rules);
            }
        } else {
            if (config('realoquent.features.generate_validation')) {
                foreach ($this->columns as $column) {
                    if (! $column->fillable) {
                        continue;
                    }
                    $column->setValidation(
                        $column->generateDefaultValidation(
                            isUnique: $this->doesColumnHaveUniqueIndex($column->name)
                        )
                    );
                }
            }
        }

        // Validation Groups
        $colGroups = [];
        foreach ($modelInfo->validationGroups as $group => $groupColumns) {
            foreach ($groupColumns as $groupColumn) {
                if (! isset($colGroups[$groupColumn])) {
                    $colGroups[$groupColumn] = [];
                }
                $colGroups[$groupColumn][] = $group;
            }
        }
        foreach ($colGroups as $column => $groups) {
            $this->columns[$column]->setValidationGroups($groups);
        }
    }

    public static function fromDBAL(DoctrineTable $dbalTable): self
    {
        $table = new self(
            name: $dbalTable->getName(),
            realoquentId: RealoquentHelpers::newId()
        );

        foreach ($dbalTable->getColumns() as $column) {
            $table->addColumn(Column::fromDBAL($column, $table->name));
        }

        foreach ($dbalTable->getIndexes() as $index) {
            $table->addIndex(Index::fromDBAL($index, $table->name));
        }

        return $table;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public static function fromSchemaArray(string $name, array $schema): self
    {
        $table = new self($name, $schema['realoquentId'] ?? null);

        $columns = $schema['columns'] ?? [];
        $indexes = $schema['indexes'] ?? [];
        unset($schema['columns'], $schema['indexes']);

        foreach ($schema as $key => $value) {
            if (property_exists($table, $key)) {
                if ($key !== 'realoquentId') {
                    $table->{$key} = $value;
                }
            } else {
                throw new \RuntimeException('Unknown table property ['.$key.'] on table ['.$name.']');
            }
        }

        foreach ($columns as $columnName => $column) {

            $columnObj = Column::fromSchemaArray($columnName, $column, $table->name);
            $table->addColumn($columnObj);
            $indexType = match (true) {
                ($column['primary'] ?? false) === true => IndexType::primary,
                ($column['unique'] ?? false) === true => IndexType::unique,
                ($column['fulltext'] ?? false) === true => IndexType::fullText,
                ($column['index'] ?? false) === true => IndexType::index,
                default => null,
            };
            if (! is_null($indexType)) {
                $table->addIndex(Index::newInferredIndex($indexType, $columnObj));
                if ($indexType === IndexType::unique || $indexType === IndexType::primary) {
                    $columnObj->setValidation(['unique:'.$table->name]);
                }
            }
        }

        foreach ($indexes as $indexName => $index) {
            $table->addIndex(Index::fromSchemaArray($indexName, $index, $table->name));
        }

        return $table;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchemaArray(): array
    {
        $schema = [
            'realoquentId' => $this->realoquentId ?: RealoquentHelpers::newId(),
        ];

        $schema['model'] = $this->model ?? false;

        $schema['columns'] = [];
        foreach ($this->getColumns() as $column) {
            $schema['columns'][$column->name] = $column->toSchemaArray();
        }

        foreach ($this->getIndexes() as $index) {
            if ($index->isSingleColumn) {
                // For single column indexes, add the shorthand on the column
                $shorthand = $index->type->value;
                $column = $index->indexColumns[0];
                $colId = $schema['columns'][$column]['realoquentId'];
                unset($schema['columns'][$column]['realoquentId']);
                $schema['columns'][$column][$shorthand] = true;
                $schema['columns'][$column]['realoquentId'] = $colId; // Keep the ID as last item
            } else {
                $schema['indexes'][$index->name] = $index->toSchemaArray();
            }
        }

        return $schema;
    }

    public function doesColumnHaveUniqueIndex(string $name): bool
    {
        return collect($this->getIndexes())
            ->filter(fn (Index $index) => $index->type === IndexType::unique || $index->type === IndexType::primary)
            ->filter(fn (Index $index) => count($index->indexColumns) === 1 && in_array($name, $index->indexColumns, true))
            ->isNotEmpty();
    }
}
