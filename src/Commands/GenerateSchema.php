<?php

namespace NovaHorizons\Realoquent\Commands;

use Illuminate\Console\Command;
use NovaHorizons\Realoquent\RealoquentManager;

class GenerateSchema extends Command
{
    /**
     * @var string
     */
    protected $signature = 'realoquent:generate-schema {--force} {--s|split-tables}';

    /**
     * @var string
     */
    protected $description = 'Reverse engineer the database and Eloquent models into a schema PHP file.';

    /**
     * @throws \Throwable
     */
    public function handle(RealoquentManager $manager): int
    {
        $schemaManager = $manager->getSchemaManager();
        if ($schemaManager->schemaExists() && ! $this->option('force')) {
            $this->newLine();
            $this->warn('Realoquent Schema file already exists. Generally this command only should be run during initial Realoquent setup.');
            if (! $this->confirm(' Do you want to overwrite it?')) {
                $this->warn('Realoquent Schema file not generated.');

                return 1;
            }
        }

        $schema = $manager->generateAndWriteSchema(splitTables: $this->option('split-tables'));

        if ($schema->getOrphanModels()->count()) {
            $this->warn('The following models were found in code, but not in the database:');
            foreach ($schema->getOrphanModels() as $possibleTableName => $orphanModel) {
                $this->line(' - '.$orphanModel.' (expected table '.$possibleTableName.')');
            }
            $this->line('If these *do match* a table in the database, review your schema.php file:');
            $this->line("Add a property to the appropriate table like: 'model' => ".$schema->getOrphanModels()->first().'::class,');
            $this->line('Then run `php artisan realoquent:diff` to regenerate your models with the correct table name set.');
            $this->newLine();
        }

        $this->info('Realoquent Schema file generated successfully: '.$schemaManager->getSchemaPath());

        $files = [$schemaManager->getSchemaPath()];
        if ($schemaManager->isUsingSplitSchema()) {
            $files[] = $schemaManager->getSplitSchemaPath();
        }

        $manager->runCodeStyleFixer($files);

        return 0;
    }
}
