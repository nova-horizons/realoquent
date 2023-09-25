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
        protected readonly string $modelNamespace,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function writeSchema(): void
    {
        $result = file_put_contents($this->schemaPath, $this->schemaToPhpString());
        throw_unless($result, new \RuntimeException('The Realoquent schema ['.$this->schemaPath.'] could not be written.'));
    }

    public function schemaToPhpString(): string
    {
        $schemaArray = $this->schema->toSchemaArray();

        $schemaString = RealoquentHelpers::printArray($schemaArray);

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

        $schemaString = preg_replace(array_keys($patterns), array_values($patterns), $schemaString);

        $uses = collect([ColumnType::class, IndexType::class, RelationshipType::class])
            ->map(function (string $class) {
                return "use {$class};";
            })
            ->implode("\n");

        return "<?php\n\n{$uses}\n\nreturn {$schemaString};\n";
    }
}
