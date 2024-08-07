<?php

namespace NovaHorizons\Realoquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\DataObjects\SchemaChanges;
use NovaHorizons\Realoquent\Exceptions\DuplicateIdException;
use NovaHorizons\Realoquent\Exceptions\UserAbortedCommandException;
use NovaHorizons\Realoquent\RealoquentHelpers;
use NovaHorizons\Realoquent\RealoquentManager;
use NovaHorizons\Realoquent\Writer\MigrationWriter;
use NovaHorizons\Realoquent\Writer\ModelWriter;
use Throwable;

class Diff extends Command
{
    /**
     * @var string
     */
    protected $signature = 'realoquent:diff';

    /**
     * @var string
     */
    protected $description = 'Transform schema changes to migrations and model updates';

    /**
     * @throws Throwable
     */
    public function handle(RealoquentManager $manager): int
    {
        $schemaManager = $manager->getSchemaManager();

        if (! $schemaManager->schemaExists()) {
            $this->error('Realoquent Schema file does not exist. Please run realoquent:generate-schema first.');

            return 1;
        }

        if (! $schemaManager->schemaSnapshotExists()) {
            $this->error('Realoquent Schema snapshot file does not exist. Please run realoquent:generate-schema first.');

            return 1;
        }

        $newSchema = $schemaManager->loadSchema();

        try {
            $changes = $schemaManager->diffSchemaAndGetChanges();
        } catch (DuplicateIdException $e) {
            $this->newLine();
            $this->error($e->getMessage());
            $this->warn('If you are trying to add a new item, remove the `realoquentId` property and a new ID will be generated for you.');
            $this->newLine();

            return 1;
        }

        $this->line($changes->prettyPrint());

        if (! $changes->hasChanges()) {
            $this->newLine();

            return 0;
        }

        if (! $this->confirm('Review the changes above. Proceed?', true)) {
            return 0;
        }

        try {
            if ($manager->shouldGenerateMigrations()) {
                $this->generateMigrations($manager, $changes);
            }

            if ($manager->shouldGenerateModels()) {
                $this->generateModels($changes, $newSchema, $manager);
            }
        } catch (UserAbortedCommandException $e) {
            $this->info('Diff aborted.');

            return 0;
        }

        $manager->getSchemaManager()->writeSchema(schema: $newSchema, splitTables: is_dir($schemaManager->getSplitSchemaPath()));
        $manager->getSchemaManager()->makeSchemaSnapshot();

        $this->info('Diff complete!');

        return 0;
    }

    /**
     * @throws Throwable
     */
    protected function generateMigrations(RealoquentManager $manager, SchemaChanges $changes): void
    {
        $migrationWriter = new MigrationWriter;
        $migration = $migrationWriter->buildFunctionBody($changes);
        if (empty($migration)) {
            return;
        }

        if (! $this->confirm('Generate migrations?', true)) {
            return;
        }

        $this->line($migration);

        if (! $this->confirm('Review the above migration. Proceed? (You will have a chance to edit before running)', true)) {
            throw new UserAbortedCommandException;
        }

        $name = $this->ask('Enter migration name (your text will be slugified)', 'schema_migration');
        $name = Str::slug($name, '_');

        $migration = $migrationWriter->createMigrationFile(
            migrationDir: $manager->getMigrationDir(),
            migrationBody: $migration,
            migrationName: $name
        );

        if (is_null($migration)) {
            $this->info('No schema changes to migrate. Migration skipped.');
            $this->newLine();
        } else {
            $manager->runCodeStyleFixer([$migration]);
            $this->info(' Migration file created: '.$migration);

            if (! $this->confirm('Review the above migration. Proceed to run migrations?', true)) {
                throw new UserAbortedCommandException;
            }
            $this->call('migrate');
        }
    }

    /**
     * @throws Throwable
     */
    protected function generateModels(SchemaChanges $changes, Schema &$newSchema, RealoquentManager $manager): void
    {
        $modifiedFiles = [];
        $models = $changes->getAffectedModels($newSchema);

        $this->line('Models to generate:');
        $this->line('    '.implode(PHP_EOL.'    ', $models));

        if (! $this->confirm('Update/generate these models?', true)) {
            return;
        }

        $tables = $changes->getAffectedTables();
        foreach ($tables as $table) {
            if (! isset($newSchema->getTables()[$table])) {
                // Table was removed
                continue;
            }
            $tableObj = $newSchema->getTables()[$table];
            if (! $tableObj->shouldHaveModel()) {
                continue;
            }
            $modelWriter = new ModelWriter(
                table: $tableObj,
                modelNamespace: $manager->getModelNamespace(),
                modelDir: $manager->getModelDir()
            );
            $modelFiles = $modelWriter->writeModel();
            $modifiedFiles = array_merge($modifiedFiles, $modelFiles);
            if (isset($tableObj->model) && $tableObj->model === true) {
                $newSchema->getTables()[$table]->setAndParseModel(RealoquentHelpers::buildModelName($manager->getModelNamespace(), $tableObj->name));
            }
        }
        if ($manager->shouldRunCodeStyleFixer() && ! empty($modifiedFiles)) {
            $this->newLine();
            $this->line('Running code style fixer on new files');
            $this->withProgressBar($modifiedFiles, function (string $modifiedFile) use ($manager) {
                $manager->runCodeStyleFixer([$modifiedFile]);
            });
            $this->newLine();
            $this->newLine();
        }
    }
}
