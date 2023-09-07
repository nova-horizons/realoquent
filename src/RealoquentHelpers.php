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
}
