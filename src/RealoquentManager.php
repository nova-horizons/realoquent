<?php

namespace NovaHorizons\Realoquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use NovaHorizons\Realoquent\DataObjects\ModelInfo;
use NovaHorizons\Realoquent\DataObjects\Schema;
use Symfony\Component\Finder\SplFileInfo;

class RealoquentManager
{
    protected string $modelDir;

    protected string $migrationDir;

    protected string $configDir;

    protected string $storageDir;

    protected string $modelNamespace;

    protected ?string $csFixerCommand;

    protected bool $generateMigrations = true;

    protected bool $generateModels = true;

    protected SchemaManager $schemaManager;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(?array $config = null)
    {
        if (! $config) {
            throw new \RuntimeException('Realoquent config not provided. See Realoquent Setup docs on how to publish config file.');
        }

        $this->loadConfig($config);
        $this->schemaManager = new SchemaManager(
            configDir: $this->configDir,
            storageDir: $this->storageDir,
            modelNamespace: $this->modelNamespace,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function loadConfig(array $config): void
    {
        $this->migrationDir = $config['migrations_dir'] ?? database_path('migrations');
        $this->modelDir = $config['model_dir'] ?? app_path('Models');
        $this->modelNamespace = $config['model_namespace'] ?? 'App\\Models';
        $this->modelNamespace = trim($this->modelNamespace, '\\').'\\';
        $this->generateMigrations = $config['features']['generate_migrations'] ?? true;
        $this->generateModels = $config['features']['generate_models'] ?? true;
        $this->csFixerCommand = $config['cs_fixer_command'] ?? null;
        $configDir = $config['schema_dir'] ?? database_path('realoquent');
        $storageDir = $config['storage_dir'] ?? storage_path('app/realoquent');
        RealoquentHelpers::validateDirectory($configDir);
        RealoquentHelpers::validateDirectory($storageDir);
        $this->configDir = $configDir;
        $this->storageDir = $storageDir;
    }

    /**
     * @throws \Throwable
     */
    public function generateAndWriteSchema(bool $splitTables = false): Schema
    {
        $schema = $this->generateSchema();
        $this->schemaManager->writeSchema($schema, $splitTables);
        $this->schemaManager->makeSchemaSnapshot();

        return $schema;
    }

    /**
     * @throws \ReflectionException
     */
    public function generateSchema(): Schema
    {
        return $this->schemaManager->rebuildSchema(
            models: $this->getModels(),
            dbTables: DatabaseAnalyzer::getTables(),
        );
    }

    /**
     * @return Collection<string, non-falsy-string>
     */
    public function getModels(): Collection
    {
        return collect(File::allFiles($this->modelDir))
            ->map(function (SplFileInfo $item) {
                $path = $item->getRelativePathName();

                // Convert path to fully qualified class name
                return sprintf('\%s%s',
                    $this->modelNamespace,
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\'));
            })
            ->filter(function (string $class) {
                return ModelInfo::isEloquentModel($class);
            })->mapWithKeys(function (string $class) {
                /** @var Model $model */
                $model = new $class;

                return [$model->getTable() => $class];
            });
    }

    /**
     * @param  string[]  $modifiedFiles
     *
     * @throws \Throwable
     */
    public function runCodeStyleFixer(array $modifiedFiles): void
    {
        if (empty($this->csFixerCommand)) {
            return;
        }

        $commands = [];
        if (str_contains($this->csFixerCommand, '{file}')) {
            foreach ($modifiedFiles as $file) {
                $commands[] = str_replace('{file}', escapeshellarg($file), $this->csFixerCommand);
            }
        } else {
            $commands[] = $this->csFixerCommand;
        }

        foreach ($commands as $command) {
            $result = Process::path(base_path())->run($command);
            throw_unless($result->successful(), new \RuntimeException('Failed to run code style fixer. Check `cs_fixer_command` config value. '.$result->errorOutput()));

        }
    }

    public function getModelNamespace(): string
    {
        return $this->modelNamespace;
    }

    public function getModelDir(): string
    {
        return $this->modelDir;
    }

    public function getSchemaManager(): SchemaManager
    {
        return $this->schemaManager;
    }

    public function getMigrationDir(): string
    {
        return $this->migrationDir;
    }

    public function shouldRunCodeStyleFixer(): bool
    {
        return ! empty($this->csFixerCommand);
    }

    public function shouldGenerateMigrations(): bool
    {
        return $this->generateMigrations;
    }

    public function shouldGenerateModels(): bool
    {
        return $this->generateModels;
    }
}
