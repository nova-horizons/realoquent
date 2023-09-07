<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Illuminate\Database\Eloquent\Model;
use NovaHorizons\Realoquent\Enums\RelationshipType;

class Relation
{
    public function __construct(
        public readonly RelationshipType $type,
        /** @var class-string<Model> */
        public readonly string $model,
        /** @var class-string<Model> */
        public readonly string $relatedModel,
        public readonly string $name,
        public readonly string $tableName,
        public readonly ?string $realoquentId = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public static function fromSchemaArray(string $tableName, string $model, string $name, array $schema): self
    {
        return new self(
            type: $schema['type'],
            model: $model,
            relatedModel: $schema['relatedTo'],
            name: $name,
            tableName: $tableName,
        );
    }
}
