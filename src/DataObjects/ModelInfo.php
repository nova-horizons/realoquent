<?php

namespace NovaHorizons\Realoquent\DataObjects;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;

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
}
