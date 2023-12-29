<?php

namespace NovaHorizons\Realoquent\Writer;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PsrPrinter;
use NovaHorizons\Realoquent\DataObjects\Column;
use NovaHorizons\Realoquent\DataObjects\Index;
use NovaHorizons\Realoquent\DataObjects\SchemaChanges;
use NovaHorizons\Realoquent\DataObjects\Table;
use NovaHorizons\Realoquent\RealoquentHelpers;

class MigrationWriter
{
    /**
     * @throws \Throwable
     *
     * @see vendor/laravel/framework/src/Illuminate/Database/Migrations/stubs/migration.stub
     */
    public function createMigrationFile(string $migrationDir, string $migrationBody, string $migrationName): ?string
    {
        $class = new ClassType(null);
        $class->setExtends(Migration::class);

        if (empty($migrationBody)) {
            return null;
        }

        $class->addMethod('up')->setBody($migrationBody)->setReturnType('void');
        $class->addMethod('down')->setBody('//')->setReturnType('void');

        $classString = (new PsrPrinter())->printClass($class);
        $classString = "<?php\n\nuse ".Schema::class.";\n\nreturn new class {$classString};";

        $file = $migrationDir.'/'.date('Y_m_d_His').'_'.$migrationName.'.php';

        $result = file_put_contents($file, $classString);

        throw_if($result === false, new \RuntimeException('Failed to write migration file: '.$file));

        return $file;
    }

    public function buildFunctionBody(SchemaChanges $changes): string
    {
        $output = '';
        foreach ($changes->changes as $changeType => $changes) {
            foreach ($changes as $name => $change) {
                $output .= match ($changeType) {
                    'table_new' => $this->getNewTableMigration($change),
                    'table_updated' => $this->getUpdatedTableMigration($name, $change),
                    'table_renamed' => $this->getRenameTableMigration($name, $change),
                    'table_removed' => $this->getRemoveTableMigration($change),
                    'column_new' => $this->getNewColumnMigration($change),
                    'column_updated' => $this->getUpdatedColumnMigration($name, $change),
                    'column_renamed' => $this->getRenameColumnMigration($name, $change),
                    'column_removed' => $this->getRemoveColumnMigration($change),
                    'index_new' => $this->getNewIndexMigration($change),
                    'index_updated' => $this->getUpdatedIndexMigration($name, $change),
                    'index_renamed' => $this->getRenameIndexMigration($name, $change),
                    'index_removed' => $this->getRemoveIndexMigration($change),
                    default => throw new \RuntimeException('Unknown change type: '.$changeType),
                };
                $output .= PHP_EOL.PHP_EOL;
            }
        }

        $output = trim($output, ' '.PHP_EOL);

        return $output;
    }

    private function getNewTableMigration(Table $table): string
    {
        $migration = "Schema::create('{$table->name}', function (\Illuminate\Database\Schema\Blueprint \$table) {".PHP_EOL;
        foreach ($table->getColumns() as $column) {
            $migration .= '    '.$this->columnMigrationLine($column).';'.PHP_EOL;
        }
        $migration .= '});';

        return $migration;
    }

    private function getNewColumnMigration(Column $column): string
    {
        return "Schema::table('{$column->tableName}', function (\Illuminate\Database\Schema\Blueprint \$table) {".PHP_EOL
                .'     '.$this->columnMigrationLine($column).';'.PHP_EOL
                .'});';

    }

    private function getNewIndexMigration(Index $index): string
    {
        return "Schema::table('{$index->tableName}', function (\Illuminate\Database\Schema\Blueprint \$table) {".PHP_EOL
                .'    '.$this->indexMigrationLine($index).';'.PHP_EOL
                .'});';
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function getUpdatedTableMigration(string $name, array $changes): string
    {
        return ''; // Currently any table level updates are only affecting models, no migration needed
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function getUpdatedColumnMigration(string $name, array $changes): string
    {
        $migrationFields = array_diff_key($changes['changes'], array_flip(Column::$ignoreMigrationFields));
        if (empty($migrationFields)) {
            return '';
        }

        [$tableName, $columnName] = explode('.', $name);
        /** @var Column $column */
        $column = $changes['state'];

        return "Schema::table('{$tableName}', function (\Illuminate\Database\Schema\Blueprint \$table) {".PHP_EOL
                .'     '.$this->columnMigrationLine($column).'->change();'.PHP_EOL
                .'});';
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function getUpdatedIndexMigration(string $name, array $changes): string
    {
        [$tableName, $indexName] = explode('.', $name);

        return "Schema::table('{$tableName}', function (\Illuminate\Database\Schema\Blueprint \$table) {".PHP_EOL
                ."    \$table->dropIndex('{$indexName}');".PHP_EOL
                .'    '.$this->indexMigrationLine($changes['state']).';'.PHP_EOL
                .'});';
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function getRenameTableMigration(string $name, array $changes): string
    {
        return "Schema::rename('{$changes['changes']['name']['old']}', '$name');";
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function getRenameColumnMigration(string $name, array $changes): string
    {
        [$tableName, $columnName] = explode('.', $name);

        return "Schema::table('{$tableName}', function(\Illuminate\Database\Schema\Blueprint \$table) {
            \$table->renameColumn('{$changes['changes']['name']['old']}', '{$columnName}');
        });";
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function getRenameIndexMigration(string $name, array $changes): string
    {
        [$tableName, $indexName] = explode('.', $name);

        return "Schema::table('{$tableName}', function(\Illuminate\Database\Schema\Blueprint \$table) {
            \$table->renameIndex('{$changes['changes']['name']['old']}', '{$indexName}');
        });";
    }

    private function getRemoveTableMigration(Table $table): string
    {
        return "Schema::drop('$table->name');";
    }

    private function getRemoveColumnMigration(Column $column): string
    {
        return "Schema::table('{$column->tableName}', function(\Illuminate\Database\Schema\Blueprint \$table) {
            \$table->dropColumn('{$column->name}');
        });";
    }

    private function getRemoveIndexMigration(Index $index): string
    {
        $function = $index->type->getDropMigrationFunction();

        return "Schema::table('{$index->tableName}', function (\Illuminate\Database\Schema\Blueprint \$table) {".PHP_EOL
                ."    \$table->{$function}('{$index->name}');".PHP_EOL
                .'});';
    }

    public function columnMigrationLine(Column $column): string
    {
        $str = "\$table->{$column->type->getMigrationFunction()}('{$column->name}'";

        if (isset($column->length)) {
            $str .= ", length: {$column->length}";
        }

        if (isset($column->precision)) {
            $str .= ", total: {$column->precision}";
        }

        if (isset($column->scale)) {
            $str .= ", places: {$column->scale}";
        }

        $str .= ')';

        if ($column->unsigned) {
            $str .= '->unsigned()';
        }

        if ($column->nullable) {
            $str .= '->nullable()';
        }

        if (! is_null($column->default)) {
            $str .= '->default('.RealoquentHelpers::printVar($column->default).')';
        }

        if ($column->autoIncrement) {
            $str .= '->autoIncrement()';
        }

        return $str;
    }

    public function indexMigrationLine(Index $index): string
    {
        $str = '$table->'.$index->type->getMigrationFunction();
        $str .= '(columns: '.RealoquentHelpers::printVar($index->indexColumns);
        $str .= ', name: '.RealoquentHelpers::printVar($index->name).')';

        return $str;
    }
}
