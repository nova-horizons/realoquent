<?php

namespace NovaHorizons\Realoquent\Commands;

use Illuminate\Console\Command;
use NovaHorizons\Realoquent\RealoquentManager;

class GenerateSchema extends Command
{
    /**
     * @var string
     */
    protected $signature = 'realoquent:generate-schema {--force}';

    /**
     * @var string
     */
    protected $description = 'Reverse engineer the database and Eloquent models into a schema PHP file.';

    /**
     * @throws \Throwable
     * @throws \Doctrine\DBAL\Exception
     */
    public function handle(RealoquentManager $manager): int
    {
        $schemaManager = $manager->getSchemaManager();
        if ($schemaManager->schemaExists() && ! $this->option('force')) {
            $this->newLine();
            $this->error('Realoquent Schema file already exists. Generally this command only should be run during initial Realoquent setup.');
            if (! $this->confirm(' Do you want to overwrite it?')) {
                $this->warn('Realoquent Schema file not generated.');

                return 1;
            }
        }

        $manager->generateAndWriteSchema();

        $this->info('Realoquent Schema file generated successfully: '.$schemaManager->getSchemaPath());

        $manager->runCodeStyleFixer([$schemaManager->getSchemaPath()]);

        return 0;
    }
}
