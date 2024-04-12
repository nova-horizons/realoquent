<?php

namespace NovaHorizons\Realoquent\Commands;

use Illuminate\Console\Command;
use NovaHorizons\Realoquent\Exceptions\DuplicateIdException;
use NovaHorizons\Realoquent\RealoquentManager;
use NovaHorizons\Realoquent\Writer\ModelWriter;

class GenerateModels extends Command
{
    /**
     * @var string
     */
    protected $signature = 'realoquent:generate-models {--force}';

    /**
     * @var string
     */
    protected $description = 'Generate Eloquent models based on schema file.';

    /**
     * @throws \Throwable
     */
    public function handle(RealoquentManager $manager): int
    {
        $schemaManager = $manager->getSchemaManager();

        $this->newLine();

        if (! $schemaManager->schemaSnapshotExists()) {
            $this->error('Realoquent Schema file does not exist. Please run realoquent:generate-schema first.');

            return 1;
        }

        try {
            $changes = $schemaManager->diffSchemaAndGetChanges();
            $hasSchemaBeenModified = $changes->hasChanges();
        } catch (DuplicateIdException $e) {
            $hasSchemaBeenModified = true;
        }

        if ($hasSchemaBeenModified && ! $this->option('force')) {
            $this->warn('Schema has been modified since last snapshot.');
            $this->warn('If you made changes to schema.php, you should run realoquent:diff instead');
            $this->warn('If you really want to continue, re-run with --force option');

            return 1;
        }

        if (! $this->option('force') && ! $this->confirm('Are you sure you want to generate new Eloquent models and base models?', true)) {
            return 0;
        }

        $schema = $schemaManager->loadSchemaSnapshot();

        $modifiedFiles = [];
        foreach ($schema->getTables() as $table) {
            if (! $table->shouldHaveModel()) {
                continue;
            }
            $modelWriter = new ModelWriter(
                table: $table,
                modelNamespace: $manager->getModelNamespace(),
                modelDir: $manager->getModelDir()
            );
            $modelFiles = $modelWriter->writeModel();
            $modifiedFiles = array_merge($modifiedFiles, $modelFiles);
        }

        $this->info('Generated '.count($modifiedFiles).' files');

        if ($manager->shouldRunCodeStyleFixer() && ! empty($modifiedFiles)) {
            $this->newLine();
            $this->line('Running code style fixer on new files');
            $this->withProgressBar($modifiedFiles, function (string $modifiedFile) use ($manager) {
                $manager->runCodeStyleFixer([$modifiedFile]);
            });
            $this->newLine();
            $this->newLine();
        }

        return 0;
    }
}
