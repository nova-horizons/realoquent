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
        // TODO Architecture tests to keep these up to date would be nice (maybe from ShowModelCommand::$relationMethods)
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
            default => throw new \InvalidArgumentException('Unknown relationship method: '.$method),
        };
    }

    public function getRelationshipFunction(): string
    {
        return $this->value;
    }

    public function getInverse(): self
    {
        // TODO Finish this
        return match ($this) {
            self::hasOne => self::belongsTo,
            self::belongsTo => self::hasOne,
            default => throw new \InvalidArgumentException('Missing inverse configuration for relation: '.$this->value),
        };
    }

    public function isSupported(): bool
    {
        // TODO Support these
        return match ($this) {
            self::hasOne => false,
            self::hasMany => false,
            self::hasOneThrough => false,
            self::hasManyThrough => false,
            self::belongsToMany => false,
            self::morphMany => false,
            self::morphTo => false,
            self::morphOne => false,
            self::morphToMany => false,
            default => true,
        };
    }

    /**
     * @return class-string
     */
    public function getReturnType(): string
    {
        // TODO Finish this
        return match ($this) {
            self::belongsTo => BelongsTo::class,
            default => throw new \InvalidArgumentException('Missing return type configuration for relation: '.$this->value),
        };
    }

    public function getRelationMethod(): string
    {
        return $this->value;
    }
}
