<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Illuminate\Database\Console\ShowModelCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use NovaHorizons\Realoquent\Enums\RelationshipType;
use NovaHorizons\Realoquent\RealoquentHelpers;
use ReflectionMethod;
use SplFileObject;

class ModelInfo
{
    public readonly string $name;

    public readonly string $tableName;

    public readonly string $namespace;

    public readonly string $primaryKey;

    public readonly string $keyType;

    public readonly ?string $extends;

    /** @var array<string, string> */
    public readonly array $uses;

    /** @var array<string, string> */
    public readonly array $fillable;

    /** @var array<string, string> */
    public readonly array $guarded;

    /** @var array<string, string> */
    public readonly array $casts;

    /** @var array<string, string[]> */
    public readonly array $validation;

    /** @var array<string, string[]> */
    public readonly array $validationGroups;

    /** @var Relation[] */
    public readonly array $relations;

    /**
     * @throws \Throwable
     */
    public function __construct(string $className)
    {
        if (! self::isEloquentModel($className)) {
            throw new \InvalidArgumentException('The given class is not an Eloquent model: '.$className);
        }

        /** @var Model $model */
        $model = new $className;
        $this->name = $model::class;
        $this->namespace = Str::beforeLast($this->name, '\\');

        // Eloquent info
        $this->tableName = $model->getTable();
        $this->primaryKey = $model->getKeyName();
        $this->keyType = $model->getKeyType();

        $this->fillable = $model->getFillable();
        $this->guarded = $model->getGuarded();
        $casts = $model->getCasts();
        foreach ($casts as &$cast) {
            // Eloquent has some duplicate primitive casts, prefer the verbose versions
            $cast = match ($cast) {
                'int' => 'integer',
                'bool' => 'boolean',
                default => $cast,
            };
        }
        $this->casts = $casts;

        // PHP info
        $reflector = new \ReflectionClass($model);
        $file = PhpFile::fromCode(file_get_contents($reflector->getFileName()));
        /** @var ClassType $class */
        $class = $file->getClasses()[$this->name] ?? null;
        throw_unless($class, new \RuntimeException('Failed to find class '.$this->name.' in file '.$reflector->getFileName()));

        $this->uses = $file->getNamespaces()[$this->namespace]->getUses();

        if ($reflector->hasProperty('validation')) {
            $this->validation = $reflector->getProperty('validation')->getValue($model);
        } else {
            $this->validation = [];
        }
        if ($reflector->hasProperty('validationGroups')) {
            $this->validationGroups = $reflector->getProperty('validationGroups')->getValue($model);
        } else {
            $this->validationGroups = [];
        }

        // Sometimes Eloquent models may extend things like Illuminate\Foundation\Auth\User
        // If we're already extending our BaseModel, find out what that extends so we can maintain it
        $extends = $class->getExtends();
        if (str_contains($extends, '\\BaseModels\\Base')) { // TODO Hardcoding this here isn't great
            $this->extends = get_parent_class($extends);
        } else {
            $this->extends = $extends;
        }

        $this->relations = []; // TODO-Relationships $this->getRelations();
    }

    /**
     * @return Relation[]
     */
    protected function getRelations(): array
    {
        $relationMethods = $this->getRelationMethodsForClass($this->name);
        $relations = [];
        foreach ($relationMethods as $relation) {

            $type = RelationshipType::fromEloquentMethod($relation['type']);
            if (! $type->isSupported()) {
                continue;
            }

            $relationMethod = (new $this->name())->{$relation['name']}();
            $localKey = $this->primaryKey;
            $foreignKey = Str::snake(class_basename($this->name)).'_'.$this->primaryKey;
            if ($relationMethod instanceof BelongsTo) {
                $localKey = $relationMethod->getForeignKeyName();
                $foreignKey = $relationMethod->getOwnerKeyName();
            } else {
                // ray($relationMethod);
            }

            $relations[$relation['name']] = new Relation(
                type: RelationshipType::fromEloquentMethod($relation['type']),
                relationName: $relation['name'],
                localModel: $this->name,
                relatedModel: $relation['related'],
                localTableName: $this->tableName,
                foreignTableName: (new $relation['related'])->getTable(),
                localKey: $localKey,
                foreignKey: $foreignKey,
                realoquentId: RealoquentHelpers::newId(),
            );
        }

        return $relations;
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
     * @see ShowModelCommand::getRelations() source of this logic
     *
     * @param  class-string  $class
     * @return array<int, array<string, string>>
     */
    private function getRelationMethodsForClass(string $class): array
    {
        $model = new $class();

        return collect(get_class_methods($model))
            ->map(fn ($method) => new ReflectionMethod($model, $method))
            ->reject(
                fn (ReflectionMethod $method) => $method->isStatic()
                        || $method->isAbstract()
                        // Realoquent changes this rule from source, we want all parent methods up to Model level
                        || $method->getDeclaringClass()->getName() === Model::class
            )
            ->filter(function (ReflectionMethod $method) {
                $file = new SplFileObject($method->getFileName());
                $file->seek($method->getStartLine() - 1);
                $code = '';
                while ($file->key() < $method->getEndLine()) {
                    $code .= trim($file->current());
                    $file->next();
                }

                return collect(array_column(RelationshipType::cases(), 'value'))
                    ->contains(fn ($relationMethod) => str_contains($code, '$this->'.$relationMethod.'('));
            })
            ->map(function (ReflectionMethod $method) use ($model) {
                $relation = $method->invoke($model);

                if (! $relation instanceof EloquentRelation) {
                    return null;
                }

                return [
                    'name' => $method->getName(),
                    'type' => Str::afterLast(get_class($relation), '\\'),
                    'related' => get_class($relation->getRelated()),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
