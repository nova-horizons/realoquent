<?php

namespace NovaHorizons\Realoquent\Commands;

use Illuminate\Console\Command;
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

        $this->comment('If you made changes to schema.php, you should run realoquent:diff instead');

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
