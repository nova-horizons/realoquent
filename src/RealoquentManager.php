<?php

namespace NovaHorizons\Realoquent;

use Doctrine\DBAL\Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use NovaHorizons\Realoquent\DataObjects\ModelInfo;
use NovaHorizons\Realoquent\DataObjects\Schema;
use Symfony\Component\Finder\SplFileInfo;

class RealoquentManager
{
    protected string $modelDir;

    protected string $migrationDir;

    protected string $modelNamespace;

    protected ?string $csFixerCommand;

    protected bool $generateMigrations = true;

    protected bool $generateModels = true;

    protected bool $generateQueryBuilders = true;

    protected SchemaManager $schemaManager;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config)
    {
        $this->loadConfig($config);
        $this->schemaManager = new SchemaManager($config);
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
        $this->generateQueryBuilders = $config['features']['generate_query_builders'] ?? true;
        $this->csFixerCommand = $config['cs_fixer_command'] ?? null;
    }

    /**
     * @throws Exception
     * @throws \Throwable
     */
    public function generateAndWriteSchema(): Schema
    {
        $schema = $this->generateSchema();
        $this->schemaManager->writeSchema($schema, $this->modelNamespace);
        $this->schemaManager->makeSchemaSnapshot();

        return $schema;
    }

    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function generateSchema(): Schema
    {
        $doctrineManager = DB::connection()->getDoctrineSchemaManager();
        $tables = $doctrineManager->listTables();

        return $this->schemaManager->rebuildSchema(
            models: $this->getModels(),
            doctrineTables: $tables,
        );
    }

    /**
     * @return Collection<string, string>
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

    public function shouldGenerateQueryBuilders(): bool
    {
        return $this->generateQueryBuilders;
    }
}
