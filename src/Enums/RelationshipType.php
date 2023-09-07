<?php

namespace NovaHorizons\Realoquent\Enums;

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
    case morphTo = 'morphTo';
    case morphOne = 'morphOne';
    case morphToMany = 'morphToMany';
    case morphedByMany = 'morphedByMany';

    public static function fromEloquentMethod(string $method): self
    {
        // TODO Architecture tests to keep these up to date would be nice (maybe from ShowModelCommand::$relationMethods)
        return match ($method) {
            'HasOne' => self::hasOne,
            'BelongsTo' => self::belongsTo,
            'HasMany' => self::hasMany,
            'HasOneThrough' => self::hasOneThrough,
            'HasManyThrough' => self::hasManyThrough,
            'BelongsToMany' => self::belongsToMany,
            'MorphTo' => self::morphTo,
            'MorphOne' => self::morphOne,
            'MorphToMany' => self::morphToMany,
            default => throw new \InvalidArgumentException('Unknown relationship method: '.$method),
        };
    }

    public function getRelationshipFunction(): string
    {
        return $this->value;
    }
}
