<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Illuminate\Database\Eloquent\Model;
use NovaHorizons\Realoquent\Enums\RelationshipType;

class Relation
{
    /**
     * Which properties to fill from schema array
     *
     * @var string[]
     */
    private static array $fillable = [
        'type',
        'relatedModel',
        'relationName',
        'localKey',
        'foreignKey',
    ];

    public function __construct(
        public readonly RelationshipType $type,
        public readonly string $relationName,
        /** @var class-string<Model> */
        public readonly string $localModel,
        /** @var class-string<Model> */
        public readonly string $relatedModel,
        public readonly string $localTableName,
        public readonly string $foreignTableName,
        public readonly string $localKey,
        public readonly string $foreignKey,
        public readonly ?string $realoquentId = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public static function fromSchemaArray(string $tableName, string $model, string $name, array $schema): self
    {
        // Discard other Column properties we don't care about
        $schema = array_intersect_key($schema, array_flip(self::$fillable));

        $schema['localModel'] = $model;
        $schema['localTableName'] = $tableName;
        $schema['foreignTableName'] = (new $schema['relatedModel'])->getTable();

        return new self(...$schema);
    }
}
