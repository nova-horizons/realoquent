<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasUuids;

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    public $casts = [
        'images' => 'array',
        'metadata' => AsArrayObject::class,
    ];

    /** @var array<string, string[]> */
    protected array $validationGroups = [
        'create' => ['name', 'images', 'metadata'],
        'update' => ['images', 'metadata'],
    ];
}
