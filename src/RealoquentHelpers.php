<?php

namespace NovaHorizons\Realoquent;

use Illuminate\Database\Console\ShowModelCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use NovaHorizons\Realoquent\DataObjects\Relation;
use NovaHorizons\Realoquent\Enums\RelationshipType;

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

    /**
     * Check if string of classname is an Eloquent model
     */
    public static function isEloquentModel(string $class): bool
    {
        if (! class_exists($class) || empty($class)) {
            return false;
        }

        $reflection = new \ReflectionClass($class);

        return $reflection->isSubclassOf(Model::class) && ! $reflection->isAbstract();
    }

    /**
     * @return Relation[]
     */
    public static function getEloquentRelations(Model $model): array
    {

        Artisan::call(ShowModelCommand::class, [
            'model' => $model::class,
            '--json' => true,
        ]);
        $json = Artisan::output();

        $modelInfo = json_decode($json, true);

        if (empty($modelInfo)) {
            return [];
        }

        $relations = [];
        foreach ($modelInfo['relations'] as $relation) {
            $relations[$relation['name']] = new Relation(
                type: RelationshipType::fromEloquentMethod($relation['type']),
                model: $model::class,
                relatedModel: $relation['related'],
                name: $relation['name'],
                tableName: $model->getTable(),
                realoquentId: RealoquentHelpers::newId(),
            );
        }

        return $relations;
    }

    public static function newId(): string
    {
        return Str::uuid()->toString();
    }
}
