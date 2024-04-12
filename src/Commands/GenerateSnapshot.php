<?php

namespace NovaHorizons\Realoquent\Commands;

use Illuminate\Console\Command;
use NovaHorizons\Realoquent\Exceptions\DuplicateIdException;
use NovaHorizons\Realoquent\RealoquentManager;

class GenerateSnapshot extends Command
{
    /**
     * @var string
     */
    protected $signature = 'realoquent:generate-snapshot {--only-if-missing} {--force}';

    /**
     * @var string
     */
    protected $description = 'Generate local snapshot of schema for future diffs';

    /**
     * @throws \Throwable
     */
    public function handle(RealoquentManager $manager): int
    {
        $schemaManager = $manager->getSchemaManager();

        $this->newLine();

        if (! $schemaManager->schemaExists()) {
            $this->error('Realoquent Schema file does not exist. Please run realoquent:generate-schema first.');

            return 1;
        }

        if ($schemaManager->schemaSnapshotExists() && ! $this->option('force')) {
            $this->info('Realoquent Schema Snapshot already exists, skipping');

            if ($this->option('only-if-missing')) {
                return 0;
            }

            try {
                $changes = $schemaManager->diffSchemaAndGetChanges();
                $hasSchemaBeenModified = $changes->hasChanges();
            } catch (DuplicateIdException $e) {
                $hasSchemaBeenModified = true;
            }

            if ($hasSchemaBeenModified) {
                $this->warn('Schema has been modified since last snapshot.');
                $this->warn('If you made changes to schema.php, you should run realoquent:diff instead');
                $this->warn('If you really want to continue, re-run with --force option');

                return 1;
            }

            return 1;
        }

        $schemaManager->makeSchemaSnapshot();
        $this->info('Realoquent Schema Snapshot generated successfully');

        return 0;
    }
}
