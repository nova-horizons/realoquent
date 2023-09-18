<?php

use NovaHorizons\Realoquent\DataObjects\Relation;
use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\Enums\ColumnType;
use NovaHorizons\Realoquent\Enums\IndexType;

it('can detect primary key type from id types', function (string $idName, ColumnType $type) {
    $schema = Schema::fromSchemaArray([
        'users' => [
            'columns' => [
                $idName => [
                    'type' => $type,
                ],
            ],
            'indexes' => [
                'primary' => [
                    'type' => IndexType::primary,
                    'indexColumns' => [
                        $idName,
                    ],
                ],
            ],
        ],
    ]);

    expect($schema->getTables()['users']->primaryKey)->toBe($idName);
    expect($schema->getTables()['users']->keyType)->toBe('integer');
    expect($schema->getTables()['users']->incrementing)->toBe(true);
})->with(['id', 'object_id'])
    ->with([
        'id' => fn () => ColumnType::id,
        'bigIncrements' => fn () => ColumnType::bigIncrements,
    ]);

it('can detect primary key type from string', function () {
    $schema = Schema::fromSchemaArray([
        'users' => [
            'columns' => [
                'slug' => [
                    'type' => ColumnType::string,
                ],
            ],
            'indexes' => [
                'primary' => [
                    'type' => IndexType::primary,
                    'indexColumns' => [
                        'slug',
                    ],
                ],
            ],
        ],
    ]);

    expect($schema->getTables()['users']->primaryKey)->toBe('slug');
    expect($schema->getTables()['users']->keyType)->toBe('string');
    expect($schema->getTables()['users']->incrementing)->toBe(false);
});

it('can detect primary key type from uuid', function () {
    $schema = Schema::fromSchemaArray([
        'users' => [
            'columns' => [
                'uuid' => [
                    'type' => ColumnType::uuid,
                ],
            ],
            'indexes' => [
                'primary' => [
                    'type' => IndexType::primary,
                    'indexColumns' => [
                        'uuid',
                    ],
                ],
            ],
        ],
    ]);

    expect($schema->getTables()['users']->primaryKey)->toBe('uuid');
    expect($schema->getTables()['users']->keyType)->toBe('string');
    expect($schema->getTables()['users']->incrementing)->toBe(false);
});

it('generates ID when converting to array', function () {
    $schemaArray = mockSchema();
    unset($schemaArray['users']['realoquentId']);
    $schema = Schema::fromSchemaArray($schemaArray);
    $newArray = $schema->toSchemaArray();

    expect($newArray['users'])->toHaveKey('realoquentId');
    expect($newArray['users']['realoquentId'])->toBeUuid();
});

it('can detect primary key type from non-autoincrement int', function () {
    $schema = Schema::fromSchemaArray([
        'users' => [
            'columns' => [
                'id' => [
                    'type' => ColumnType::bigInteger,
                ],
            ],
            'indexes' => [
                'primary' => [
                    'type' => IndexType::primary,
                    'indexColumns' => [
                        'id',
                    ],
                ],
            ],
        ],
    ]);

    expect($schema->getTables()['users']->primaryKey)->toBe('id');
    expect($schema->getTables()['users']->keyType)->toBe('integer');
    expect($schema->getTables()['users']->incrementing)->toBe(false);
});

it('can handle relation', function () {
    $schema = Schema::fromSchemaArray([
        'users' => [
            'model' => \Tests\Models\User::class,
            'columns' => [
                'id' => [
                    'type' => ColumnType::bigIncrements,
                ],
                'team_id' => [
                    'type' => \NovaHorizons\Realoquent\Enums\RelationshipType::belongsTo,
                    'relatedModel' => \Tests\Models\Team::class,
                ],

            ],
        ],
        'teams' => [
            'model' => \Tests\Models\Team::class,
            'columns' => [
                'id' => [
                    'type' => ColumnType::mediumIncrements,
                ],
            ],
        ],
    ]);

    $usersTable = $schema->getTables()['users'];
    $userTeamIdCol = $usersTable->getColumns()['team_id'];

    expect($usersTable->getRelations()['team'])->toBeInstanceOf(Relation::class);
    expect($userTeamIdCol->type)->toBe(ColumnType::mediumInteger);
})->todo(); // TODO
