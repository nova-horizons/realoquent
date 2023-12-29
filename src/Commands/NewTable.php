<?php

namespace NovaHorizons\Realoquent\Commands;

use Illuminate\Console\Command;
use NovaHorizons\Realoquent\DataObjects\Table;
use NovaHorizons\Realoquent\Enums\ColumnType;
use NovaHorizons\Realoquent\RealoquentManager;

class NewTable extends Command
{
    /**
     * @var string
     */
    protected $signature = 'realoquent:new-table {tableName}';

    /**
     * @var string
     */
    protected $description = 'Helper to generate base schema for a new database table';

    /**
     * @throws \Throwable
     */
    public function handle(RealoquentManager $manager): int
    {
        $schemaManager = $manager->getSchemaManager();

        if (! $schemaManager->schemaExists()) {
            $this->error('Realoquent Schema file does not exist. Please run realoquent:generate-schema first.');

            return 1;
        }

        $schema = $schemaManager->loadSchema();

        $table = Table::fromSchemaArray(
            name: $this->argument('tableName'),
            schema: [
                'model' => true,
                'columns' => [
                    'id' => [
                        'type' => ColumnType::bigIncrements,
                        'guarded' => true,
                        'primary' => true,
                    ],
                    'created_at' => [
                        'type' => ColumnType::timestamp,
                        'guarded' => true,
                    ],
                    'updated_at' => [
                        'type' => ColumnType::timestamp,
                        'guarded' => true,
                    ],
                ],
            ]
        );

        $schema->addTable($table);

        $schemaManager->writeSchema(
            schema: $schema,
            splitTables: $manager->getSchemaManager()->isUsingSplitSchema()
        );

        $this->info('New table schema generated successfully');
        $this->info('Review and update the schema file and run `php artisan realoquent:diff` to apply the changes.');

        $files = [$schemaManager->getSchemaPath()];
        if ($manager->getSchemaManager()->isUsingSplitSchema()) {
            $files[] = $schemaManager->getSplitSchemaPath();
        }

        $manager->runCodeStyleFixer($files);

        return 0;
    }
}
