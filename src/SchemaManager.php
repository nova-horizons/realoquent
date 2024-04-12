<?php

namespace NovaHorizons\Realoquent;

use Illuminate\Support\Collection;
use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\DataObjects\Table;
use NovaHorizons\Realoquent\Exceptions\DuplicateIdException;
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
     * @param  array<int, string>  $dbTables
     */
    public function rebuildSchema(Collection $models, array $dbTables): Schema
    {
        $schema = new Schema();

        foreach ($dbTables as $dbTableName) {
            $realTable = Table::fromDB($dbTableName);
            $model = $models[$dbTableName] ?? null;
            if ($model) {
                unset($models[$dbTableName]);
                $realTable->setAndParseModel($model);
            }
            $schema->addTable($realTable);
        }

        $schema->setOrphanModels($models);

        return $schema;
    }

    /**
     * @throws \Throwable
     */
    public function writeSchema(Schema $schema, bool $splitTables = false): void
    {
        $writer = $this->getWriter(schema: $schema, splitTables: $splitTables);
        $writer->writeSchema();
    }

    /**
     * @throws \Throwable
     */
    public function makeSchemaSnapshot(): void
    {
        $snapshotPath = $this->getschemaSnapshotPath();

        $writer = $this->getWriter(schema: $this->loadSchema(), splitTables: false);
        $schemaString = $writer->schemaToPhpString();

        $result = file_put_contents($snapshotPath, $schemaString);
        throw_unless($result, new \RuntimeException('The Realoquent schema snapshot ['.$snapshotPath.'] could not be written.'));
    }

    /**
     * @throws DuplicateIdException
     */
    public function diffSchemaAndGetChanges(): DataObjects\SchemaChanges
    {
        $newSchema = $this->loadSchema();
        $currentSchema = $this->loadSchemaSnapshot();

        return (new SchemaDiffer(
            currentSchema: $currentSchema,
            newSchema: $newSchema,
        ))->getSchemaChanges();
    }

    public function getSchemaPath(): string
    {
        return $this->configDir.'/schema.php';
    }

    public function getSplitSchemaPath(): string
    {
        return $this->configDir.'/tables';
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

    public function isUsingSplitSchema(): bool
    {
        return is_dir($this->getSplitSchemaPath());
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

    protected function getWriter(Schema $schema, bool $splitTables): SchemaWriter
    {
        return new SchemaWriter(
            schema: $schema,
            schemaPath: $this->getSchemaPath(),
            splitSchemaPath: $this->getSplitSchemaPath(),
            modelNamespace: $this->modelNamespace,
            splitTables: $splitTables,
        );
    }
}
