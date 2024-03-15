<?php

namespace NovaHorizons\Realoquent\Enums;

use RuntimeException;

/**
 * @see https://laravel.com/docs/10.x/migrations#available-index-types Documentation on available types
 * @see \Illuminate\Database\Schema\Blueprint for implementation of each of the migration functions
 */
enum IndexType: string
{
    /*
     * Realoquent name => Laravel migration function name
     */
    case index = 'index';
    case fullText = 'fullText';
    case primary = 'primary';
    case spatialIndex = 'spatialIndex'; // TODO Untested
    case unique = 'unique';

    /**
     * @param  array<string, mixed>  $dbalIndex
     */
    public static function fromDB(array $dbalIndex): self
    {
        return match (true) {
            $dbalIndex['primary'] => self::primary,
            $dbalIndex['unique'] => self::unique,
            // TODO-DBAL $dbalIndex->hasFlag('fulltext') => self::fullText,
            // TODO-DBAL $dbalIndex->hasFlag('spatial') => self::spatialIndex,
            default => self::index,
        };
    }

    public function getDropMigrationFunction(): string
    {
        return match ($this) {
            self::primary => throw new RuntimeException('Realoquent does not support dropping the primary key. Please do so with your own migration and re-run realoquent:generate-schema.'),
            self::unique => 'dropUnique',
            self::index => 'dropIndex',
            self::fullText => 'dropFullText',
            self::spatialIndex => 'dropSpatialIndex',
            default => throw new \RuntimeException('Unknown index type ['.$this->value.']'),
        };
    }

    public function getMigrationFunction(): string
    {
        return $this->value;
    }
}
