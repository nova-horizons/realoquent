<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Illuminate\Support\Str;
use NovaHorizons\Realoquent\Writer\MigrationWriter;

class SchemaChanges
{
    public function __construct(
        /** @var array<string, array<string, mixed>> */
        public readonly array $changes
    ) {
    }

    public function prettyPrint(): string
    {
        if (! $this->hasChanges()) {
            return 'No changes detected';
        }

        $output = '';
        foreach ($this->changes as $changeType => $changes) {
            $output .= Str::of($changeType)->replace('_', ' ')->ucfirst()->append(': ', PHP_EOL);

            foreach ($changes as $name => $change) {
                $output .= "  {$name}: ".PHP_EOL;
                if (is_array($change) && is_array($change['changes'])) {
                    foreach ($change['changes'] as $key => $value) {
                        $old = is_array($value['old']) ? implode('/', $value['old']) : $value['old'];
                        $new = is_array($value['new']) ? implode('/', $value['new']) : $value['new'];
                        $output .= "    {$key}: {$old} => {$new}".PHP_EOL;
                    }
                }
            }
            $output .= PHP_EOL;
        }

        return $output;
    }

    public function getMigrationFunction(): string
    {
        $writer = new MigrationWriter();

        return $writer->buildFunctionBody($this);
    }

    /**
     * @return string[]
     */
    public function getAffectedTables(): array
    {
        $tables = [];
        foreach ($this->changes as $changeType => $changes) {
            foreach ($changes as $name => $change) {
                if (is_array($change)) {
                    $tables[] = $change['state']->tableName;
                } else {
                    $tables[] = $change->tableName;
                }
            }
        }

        $tables = array_unique($tables);
        sort($tables);

        return $tables;
    }

    /**
     * @return string[]
     */
    public function getAffectedModels(Schema $schema): array
    {
        $models = [];
        $tables = $schema->getTables();
        foreach ($this->getAffectedTables() as $table) {
            isset($tables[$table]->model) && $models[] = $tables[$table]->model;
        }

        $models = array_unique($models);
        sort($models);

        return $models;
    }

    public function hasChanges(): bool
    {
        return ! empty($this->changes);
    }
}
