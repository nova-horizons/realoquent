<?php

namespace NovaHorizons\Realoquent\Enums;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @see https://laravel.com/docs/10.x/eloquent-relationships Documentation on available types
 * @see \Illuminate\Database\Eloquent\Concerns\HasRelationships for implementation of each of the relationship functions
 * @see \Illuminate\Database\Schema\Blueprint for implementation of each of the migration functions
 */
enum RelationshipType: string
{
    case hasOne = 'hasOne';
    case belongsTo = 'belongsTo';
    case hasMany = 'hasMany';
    case hasOneThrough = 'hasOneThrough';
    case hasManyThrough = 'hasManyThrough';
    case belongsToMany = 'belongsToMany';
    case morphMany = 'morphMany';
    case morphOne = 'morphOne';
    case morphTo = 'morphTo';
    case morphToMany = 'morphToMany';
    case morphedByMany = 'morphedByMany';

    public static function fromEloquentMethod(string $method): self
    {
        return match ($method) {
            'HasOne' => self::hasOne,
            'BelongsTo' => self::belongsTo,
            'HasMany' => self::hasMany,
            'HasOneThrough' => self::hasOneThrough,
            'HasManyThrough' => self::hasManyThrough,
            'BelongsToMany' => self::belongsToMany,
            'MorphMany' => self::morphMany,
            'MorphTo' => self::morphTo,
            'MorphOne' => self::morphOne,
            'MorphToMany' => self::morphToMany,
            'MorphedByMany' => self::morphedByMany,
            default => throw new \InvalidArgumentException('Unknown relationship method: '.$method),
        };
    }

    public function getRelationshipFunction(): string
    {
        return $this->value;
    }

    public function isSupported(): bool
    {
        try {
            $this->getReturnType();

            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @return class-string
     *
     * @throws \InvalidArgumentException
     */
    public function getReturnType(): string
    {
        // TODO Add support for other relationship types
        return match ($this) {
            self::belongsTo => BelongsTo::class,
            default => throw new \InvalidArgumentException('Missing return type configuration for relation: '.$this->value),
        };
    }
}
