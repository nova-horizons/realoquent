<?php

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use NovaHorizons\Realoquent\DataObjects\ModelInfo;
use NovaHorizons\Realoquent\RealoquentHelpers;
use NovaHorizons\Realoquent\RealoquentServiceProvider;
use Tests\Models\Team;
use Tests\Models\User;

it('handles invalid models', function (string $class) {
    $info = new ModelInfo($class);
})->with([
    RealoquentHelpers::class,
    RealoquentServiceProvider::class,
])->throws(InvalidArgumentException::class);

it('can detect models', function (string $class, bool $expected) {
    expect(ModelInfo::isEloquentModel($class))->toBe($expected);
})->with([
    [Team::class, true],
    [User::class, true],
    [\Illuminate\Foundation\Auth\User::class, true],
    [RealoquentServiceProvider::class, false],
    ['not-a-class', false],
    ['', false],
]);

it('handles basic info that is explicit in model', function () {
    $info = new ModelInfo(Team::class);
    expect($info->name)->toBe(Team::class);
    expect($info->namespace)->toBe('Tests\Models');
    expect($info->tableName)->toBe('team_list');
    expect($info->primaryKey)->toBe('team_id');
    expect($info->keyType)->toBe('string');

    expect($info->fillable)->toBe(['name', 'images']);
    expect($info->guarded)->toBe(['*']);
    expect($info->casts)->toBe([
        'images' => 'array',
        'metadata' => AsArrayObject::class,
        'missing-col' => 'string',
    ]);

    expect($info->validation)->toBe([]);
    expect($info->validationGroups)->toBe([
        'create' => ['name', 'images', 'metadata'],
        'update' => ['images', 'metadata'],
    ]);
    expect($info->extends)->toBe(Model::class);
});

it('handles basic info that is not in model', function () {
    $info = new ModelInfo(User::class);
    expect($info->name)->toBe(User::class);
    expect($info->namespace)->toBe('Tests\Models');
    expect($info->tableName)->toBe('users');
    expect($info->primaryKey)->toBe('id');
    expect($info->keyType)->toBe('int');

    expect($info->fillable)->toBe([]);
    expect($info->guarded)->toBe(['*']);
    expect($info->casts)->toBe(['id' => 'integer']);

    expect($info->validation)->toBe([]);
    expect($info->validationGroups)->toBe([]);
    expect($info->extends)->toBe(\Illuminate\Foundation\Auth\User::class);
});
