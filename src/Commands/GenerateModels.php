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
     * @throws \Doctrine\DBAL\Exception
     */
    public function handle(RealoquentManager $manager): int
    {
        $schemaManager = $manager->getSchemaManager();

        $this->newLine();

        if (! $schemaManager->schemaSnapshotExists()) {
            $this->error('Realoquent Schema file does not exist. Please run realoquent:generate-schema first.');

            return 1;
        }

        $this->comment('If you made changes to schema.php, you should run realoquent:schema-diff instead');

        if (! $this->option('force') && ! $this->confirm('Are you sure you want to generate new Eloquent models and base models?', true)) {
            return 0;
        }

        $schema = $schemaManager->loadSchemaSnapshot();

        $modifiedFiles = [];
        foreach ($schema->getTables() as $table) {
            $modelWriter = new ModelWriter(
                table: $table,
                modelNamespace: $manager->getModelNamespace(),
                modelDir: $manager->getModelDir()
            );
            $modelFiles = $modelWriter->writeModel();
            $modifiedFiles = array_merge($modifiedFiles, $modelFiles);
        }

        $manager->runCodeStyleFixer($modifiedFiles);

        return 0;
    }
}
