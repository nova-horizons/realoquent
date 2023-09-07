<?php

namespace NovaHorizons\Realoquent\DataObjects;

use NovaHorizons\Realoquent\Enums\Type;

class Schema
{
    /**
     * @var Table[]
     */
    protected array $tables = [];

    public function toPhpString(string $modelNamespace): string
    {
        $schemaArray = $this->toSchemaArray();

        $schemaString = var_export($schemaArray, true);

        $modelNamespace = preg_quote($modelNamespace);
        // var_export already escapes the backslashes, so we need to double-quote our slashes in the pattern
        $modelNamespacePattern = preg_quote($modelNamespace);

        $castNamespace = preg_quote('Illuminate\\Database\\Eloquent\\Casts\\');
        $castNamespacePattern = preg_quote($castNamespace);

        // Hacky way to get the schema to be formatted nicely
        $patterns = [
            // Switch to short arrays
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
            "/=>[ ]?\n[ ]+\[/" => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
            // Remove unnecessary numeric indexes
            "/[0-9]+ => \[/" => '[',
            "/[0-9]+ => \'/" => '\'',
            // Code styling
            '/NULL/' => 'null',
            '/  /' => '    ',
            // Convert string classnames to ::class
            "/'Type::(.*?)',/" => 'Type::$1,',
            "/'Relationship::(.*?)',/" => 'Relationship::$1,',
            "/'Illuminate\\\\Database\\\\Eloquent\\\\Casts(.*?)',/" => '\\$1::class,',
            "/'{$modelNamespacePattern}(.*?)',/" => "\\{$modelNamespace}$1::class,",
            "/'{$castNamespacePattern}(.*?)',/" => "\\{$castNamespace}$1::class,",
        ];

        $schemaString = preg_replace(array_keys($patterns), array_values($patterns), $schemaString);

        $schemaString = "<?php\n\nuse ".Type::class.";\n\nreturn ".$schemaString.";\n";

        return $schemaString;
    }

    /**
     * @param  array<string, mixed>  $schemaArray
     */
    public static function fromSchemaArray(array $schemaArray): self
    {
        $schema = new self();
        foreach ($schemaArray as $tableName => $tableArray) {
            $table = Table::fromSchemaArray($tableName, $tableArray);
            $schema->addTable($table);
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchemaArray(): array
    {
        $schemaArray = [];
        foreach ($this->tables as $table) {
            $schemaArray[$table->name] = $table->toSchemaArray();
        }

        return $schemaArray;
    }

    public function addTable(Table $realTable): void
    {
        $this->tables[$realTable->name] = $realTable;
    }

    /**
     * @return array<string, Table>
     */
    public function getTables(): array
    {
        return $this->tables;
    }
}
