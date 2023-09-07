<?php

namespace NovaHorizons\Realoquent;

use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Illuminate\Support\Collection;
use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\DataObjects\Table;

class SchemaManager
{
    protected string $configDir;

    protected string $storageDir;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config)
    {
        $configDir = $config['schema_dir'] ?: database_path('realoquent');
        $storageDir = $config['storage_dir'] ?: storage_path('app/realoquent');
        RealoquentHelpers::validateDirectory($configDir);
        RealoquentHelpers::validateDirectory($storageDir);
        $this->configDir = $configDir;
        $this->storageDir = $storageDir;
    }

    /**
     * @param  Collection<string, string>  $models
     * @param  DoctrineTable[]  $doctrineTables
     *
     * @throws \Throwable
     */
    public function rebuildSchema(Collection $models, array $doctrineTables): Schema
    {
        $schema = new Schema();

        foreach ($doctrineTables as $doctrineTable) {
            $realTable = Table::fromDBAL($doctrineTable);
            $model = $models[$doctrineTable->getName()] ?? null;
            if ($model) {
                $realTable->setAndParseModel($model);
            }
            $schema->addTable($realTable);
        }

        return $schema;
    }

    /**
     * @throws \Throwable
     */
    public function writeSchema(Schema $schema, string $modelNamespace): void
    {
        $result = file_put_contents($this->getSchemaPath(), $schema->toPhpString($modelNamespace));
        throw_unless($result, new \RuntimeException('The Realoquent schema ['.$this->getSchemaPath().'] could not be written.'));
    }

    /**
     * @throws \Throwable
     */
    public function makeSchemaSnapshot(): void
    {
        $schema = $this->getSchemaPath();
        $snapshot = $this->getschemaSnapshotPath();

        $result = copy($schema, $snapshot);
        throw_unless($result, new \RuntimeException('The Realoquent schema snapshot ['.$snapshot.'] could not be written.'));
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
