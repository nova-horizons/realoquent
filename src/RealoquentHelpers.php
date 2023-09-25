<?php

namespace NovaHorizons\Realoquent;

use Illuminate\Support\Str;

class RealoquentHelpers
{
    /**
     * Validate a directory exists and is writeable.
     * Try to create if not
     * Throw exception if anything goes wrong
     */
    public static function validateDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            if (! is_writable($directory)) {
                throw new \RuntimeException('The Realoquent directory ['.$directory.'] is not writeable.');
            }
        } else {
            $result = mkdir($directory, 0755, true);
            if (! $result) {
                throw new \RuntimeException('The Realoquent directory ['.$directory.'] could not be created.');
            }
        }
    }

    public static function buildModelName(string $modelNamespace, string $tableName): string
    {
        return $modelNamespace.ucfirst(Str::studly($tableName));
    }

    public static function newId(): string
    {
        return Str::uuid()->toString();
    }

    public static function printVar(mixed $var): string
    {
        return var_export($var, true);
    }

    public static function printArray(array $var): string
    {
        $string = self::printVar($var);

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
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $string);
    }
}
