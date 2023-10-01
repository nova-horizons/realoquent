<?php

namespace NovaHorizons\Realoquent\Writer;

use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\Enums\ColumnType;
use NovaHorizons\Realoquent\Enums\IndexType;
use NovaHorizons\Realoquent\Enums\RelationshipType;
use NovaHorizons\Realoquent\RealoquentHelpers;

class SchemaWriter
{
    public function __construct(
        protected readonly Schema $schema,
        protected readonly string $schemaPath,
        protected readonly string $splitSchemaPath,
        protected readonly string $modelNamespace,
        protected readonly bool $splitTables = false,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function writeSchema(): void
    {
        if ($this->splitTables) {
            $this->writeSplitSchema();

            return;
        }

        $this->writeFile($this->schemaPath, $this->schemaToPhpString());
    }

    public function schemaToPhpString(): string
    {
        $schemaArray = $this->schema->toSchemaArray();

        $schemaString = $this->arrayToString($schemaArray);

        return $this->arrayStringToPhpFile($schemaString);
    }

    /**
     * @throws \Throwable
     */
    protected function writeSplitSchema(): void
    {
        RealoquentHelpers::validateDirectory($this->splitSchemaPath);

        $schemaArray = $this->schema->toSchemaArray();

        $tables = [];

        foreach ($schemaArray as $tableName => $table) {
            $tablePath = $this->splitSchemaPath.DIRECTORY_SEPARATOR.$tableName.'.php';
            $tables[$tableName] = 'require .'.str_replace(base_path(), '', $tablePath);
            $this->writeFile($tablePath, $this->arrayStringToPhpFile($this->arrayToString($table)));
        }

        $mainSchema = $this->arrayStringToPhpFile($this->arrayToString($tables));
        $mainSchema = str_replace("'require ", "require '", $mainSchema);

        $this->writeFile($this->schemaPath, $mainSchema);
    }

    protected function arrayToString(array $array): string
    {
        $string = RealoquentHelpers::printArray($array);

        $modelNamespace = preg_quote($this->modelNamespace);
        // var_export already escapes the backslashes, so we need to double-quote our slashes in the pattern
        $modelNamespacePattern = preg_quote($modelNamespace);

        $castNamespace = preg_quote('Illuminate\\Database\\Eloquent\\Casts\\');
        $castNamespacePattern = preg_quote($castNamespace);

        // Hacky way to get the schema to be formatted nicely
        $patterns = [
            // Code styling
            '/  /' => '    ',
            // Convert string classnames to ::class
            "/'ColumnType::(.*?)',/" => 'ColumnType::$1,',
            "/'IndexType::(.*?)',/" => 'IndexType::$1,',
            "/'RelationshipType::(.*?)',/" => 'RelationshipType::$1,',
            "/'Illuminate\\\\Database\\\\Eloquent\\\\Casts(.*?)',/" => '\\$1::class,',
            "/'{$modelNamespacePattern}(.*?)',/" => "\\{$modelNamespace}$1::class,",
            "/'{$castNamespacePattern}(.*?)',/" => "\\{$castNamespace}$1::class,",
        ];

        $string = preg_replace(array_keys($patterns), array_values($patterns), $string);

        return $string;
    }

    protected function arrayStringToPhpFile(string $string): string
    {
        $uses = collect([ColumnType::class, IndexType::class, RelationshipType::class])
            ->map(function (string $class) {
                return "use {$class};";
            })
            ->implode("\n");

        return "<?php\n\n{$uses}\n\nreturn {$string};\n";
    }

    /**
     * @throws \Throwable
     */
    protected function writeFile(string $path, string $contents): void
    {
        $result = file_put_contents($path, $contents);
        throw_unless($result, new \RuntimeException('The Realoquent schema ['.$path.'] could not be written.'));
    }
}
