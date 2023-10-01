<?php

namespace NovaHorizons\Realoquent;

use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Illuminate\Support\Collection;
use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\DataObjects\Table;
use NovaHorizons\Realoquent\Writer\SchemaWriter;

class SchemaManager
{
    public function __construct(
        protected readonly string $configDir,
        protected readonly string $storageDir,
        protected readonly string $modelNamespace,
    ) {
    }

    /**
     * @param  Collection<string, string>  $models
     * @param  DoctrineTable[]  $doctrineTables
     */
    public function rebuildSchema(Collection $models, array $doctrineTables): Schema
    {
        $schema = new Schema();

        foreach ($doctrineTables as $doctrineTable) {
            $realTable = Table::fromDBAL($doctrineTable);
            $model = $models[$doctrineTable->getName()] ?? null;
            if ($model) {
                unset($models[$doctrineTable->getName()]);
                $realTable->setAndParseModel($model);
            }
            $schema->addTable($realTable);
        }

        $schema->setOrphanModels($models);

        /* TODO-Relationships
        // Update relationship Columns to use relationship types
        // Need to do this last so all the tables and columns are populated in Schema
        foreach ($schema->getTables() as $localTable) {
            foreach ($localTable->getRelations() as $relation) {
                if (! $relation->type->isSupported()) {
                    continue;
                }
                $localColumn = $localTable->getColumns()[$relation->localKey];
                $foreignColumn = $schema->getTables()[$relation->foreignTableName]->getColumns()[$relation->foreignKey];
                $localColumn->setLocalRelationship($relation, $foreignColumn);
                // $foreignColumn->setForeignRelationshipType($relation, $localColumn);
            }
        }
        */

        return $schema;
    }

    /**
     * @throws \Throwable
     */
    public function writeSchema(Schema $schema, bool $splitTables = false): void
    {
        $writer = new SchemaWriter(
            schema: $schema,
            schemaPath: $this->getSchemaPath(),
            modelNamespace: $this->modelNamespace,
            splitTables: $splitTables,
        );
        $writer->writeSchema();
    }

    /**
     * @throws \Throwable
     */
    public function makeSchemaSnapshot(): void
    {
        $snapshotPath = $this->getschemaSnapshotPath();

        $writer = new SchemaWriter(
            schema: $this->loadSchema(),
            schemaPath: $this->getSchemaPath(),
            modelNamespace: $this->modelNamespace,
            splitTables: false,
        );
        $schemaString = $writer->schemaToPhpString();

        $result = file_put_contents($snapshotPath, $schemaString);
        throw_unless($result, new \RuntimeException('The Realoquent schema snapshot ['.$snapshotPath.'] could not be written.'));
    }

    public function getSchemaPath(): string
    {
        return $this->configDir.'/schema.php';
    }

    public function getSchemaSnapshotPath(): string
    {
        return $this->storageDir.'/schema.php';
    }

    public function schemaExists(): bool
    {
        return file_exists($this->getSchemaPath());
    }

    public function schemaSnapshotExists(): bool
    {
        return file_exists($this->getschemaSnapshotPath());
    }

    public function loadSchemaSnapshot(): Schema
    {
        $schemaArray = require $this->getSchemaSnapshotPath();

        return Schema::fromSchemaArray($schemaArray);
    }

    public function loadSchema(): Schema
    {
        $schemaArray = require $this->getSchemaPath();

        return Schema::fromSchemaArray($schemaArray);
    }
}
