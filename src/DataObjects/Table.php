<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Illuminate\Database\Eloquent\Model;
use NovaHorizons\Realoquent\Enums\RelationshipType;
use NovaHorizons\Realoquent\RealoquentHelpers;
use NovaHorizons\Realoquent\Traits\Comparable;

class Table
{
    use Comparable;

    /** @readonly */
    public readonly string $name;

    /** @readonly */
    public ?string $model;

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

    /** @var array<string, Relation> */
    protected array $relations = [];

    public function __construct(string $name, string $realoquentId = null)
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
            if ($a->isPrimary) {
                return -1;
            }

            return $a->name <=> $b->name;
        });

        if ($index->isPrimary) {
            $this->primaryKey = $index->columns[0];
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

    /**
     * @return Relation[]
     */
    public function getRelations(): array
    {
        return $this->relations;
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
            $castColumns[$column->name] = $column->cast ?: $column->type->getCast();
        }

        return $castColumns;
    }

    /**
     * @throws \Throwable
     */
    public function setAndParseModel(string $model): void
    {
        throw_unless(RealoquentHelpers::isEloquentModel($model), new \RuntimeException('The model ['.$model.'] is not a subclass of '.Model::class));

        /** @var Model $model */
        $model = new $model;
        $this->model = $model::class;
        $this->primaryKey = $model->getKeyName();
        $this->keyType = $model->getKeyType();

        // TODO Also need to run on BaseModel
        // TODO Doesn't grab parameter son the relation for custom IDs, etc
        $this->relations = RealoquentHelpers::getEloquentRelations($model);

        foreach ($model->getFillable() as $field) {
            if ($field === '*') {
                foreach ($this->columns as $column) {
                    $column->setFillable(true);
                }
            } else {
                $this->columns[$field]->setFillable(true); // TODO Need isset check? And below?
            }
        }

        foreach ($model->getGuarded() as $field) {
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

        foreach ($model->getCasts() as $field => $cast) {
            // Eloquent has some duplicate primitive casts, prefer the verbose versions
            $cast = match ($cast) {
                'int' => 'integer',
                'bool' => 'boolean',
                default => $cast,
            };

            if (! isset($this->columns[$field])) {
                continue;
            }
            if ($cast !== $this->columns[$field]->type->getCast()) {
                $this->columns[$field]->setCast($cast);
            }
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

            if ($column['type'] instanceof RelationshipType) {
                $table->relations[$columnName] = Relation::fromSchemaArray($table->name, $table->model, $columnName, $column);

                continue;
            }

            $columnObj = Column::fromSchemaArray($columnName, $column, $table->name);
            $table->addColumn($columnObj);
            if (($column['primary'] ?? false) === true) {
                $table->addIndex(Index::newInferredPrimary($columnObj));
            } elseif (($column['unique'] ?? false) === true) {
                $table->addIndex(Index::newInferredUnique($columnObj));
            } elseif (($column['index'] ?? false) === true) {
                $table->addIndex(Index::newInferredIndex($columnObj));
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
            'realoquentId' => $this->realoquentId,
        ];

        ! empty($this->model) && $schema['model'] = $this->model;

        $schema['columns'] = [];
        foreach ($this->getColumns() as $column) {
            $schema['columns'][$column->name] = $column->toSchemaArray();
        }

        foreach ($this->getIndexes() as $index) {
            if ($index->isSingleColumn) {
                $shorthand = match (true) {
                    $index->isPrimary => 'primary',
                    $index->isUnique => 'unique',
                    default => 'index',
                };
                $column = $index->columns[0];
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
}
