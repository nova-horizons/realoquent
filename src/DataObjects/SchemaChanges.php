<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Illuminate\Support\Str;
use NovaHorizons\Realoquent\RealoquentHelpers;
use NovaHorizons\Realoquent\Writer\MigrationWriter;

class SchemaChanges
{
    public function __construct(
        /** @var array<string, array<string, mixed>> */
        public readonly array $changes
    ) {}

    public function prettyPrint(): string
    {
        if (! $this->hasChanges()) {
            return 'No changes detected';
        }

        $output = '';
        foreach ($this->changes as $changeType => $changes) {
            $output .= Str::of($changeType)->explode('_')->reverse()->map(fn (string $s) => ucfirst($s))->implode(' ').': '.PHP_EOL;

            foreach ($changes as $name => $change) {
                $output .= "  {$name}";
                if (is_array($change) && is_array($change['changes'])) {
                    $output .= ': '.PHP_EOL;
                    foreach ($change['changes'] as $key => $value) {
                        $old = is_array($value['old']) ? implode('/', $value['old']) : RealoquentHelpers::printVar($value['old']);
                        $new = is_array($value['new']) ? implode('/', $value['new']) : RealoquentHelpers::printVar($value['new']);
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
        $writer = new MigrationWriter;

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
                    $tables[] = $change['state']->tableName ?? $change['state']->name;
                } else {
                    $tables[] = $change->tableName ?? $change->name;
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
            if (! isset($tables[$table])) {
                // Table was removed
                continue;
            }
            $model = $tables[$table]->model ?? true;
            if ($model === true) {
                $models[] = 'New model for: '.$table;
            } else {
                $models[] = $tables[$table]->model;
            }
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
