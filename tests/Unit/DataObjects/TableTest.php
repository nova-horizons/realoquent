<?php

use NovaHorizons\Realoquent\DataObjects\Relation;
use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\Enums\Type;

it('can detect primary key type from id types', function (string $idName, Type $type) {
    $schema = Schema::fromSchemaArray([
        'users' => [
            'columns' => [
                $idName => [
                    'type' => $type,
                ],
            ],
            'indexes' => [
                'primary' => [
                    'indexColumns' => [
                        $idName,
                    ],
                    'isPrimary' => true,
                    'isUnique' => true,
                ],
            ],
        ],
    ]);

    expect($schema->getTables()['users']->primaryKey)->toBe($idName);
    expect($schema->getTables()['users']->keyType)->toBe('integer');
    expect($schema->getTables()['users']->incrementing)->toBe(true);
})->with(['id', 'object_id'])
    ->with([
        'id' => fn () => Type::id,
        'bigIncrements' => fn () => Type::bigIncrements,
    ]);

it('can detect primary key type from string', function () {
    $schema = Schema::fromSchemaArray([
        'users' => [
            'columns' => [
                'slug' => [
                    'type' => Type::string,
                ],
            ],
            'indexes' => [
                'primary' => [
                    'indexColumns' => [
                        'slug',
                    ],
                    'isPrimary' => true,
                    'isUnique' => true,
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
                    'type' => Type::uuid,
                ],
            ],
            'indexes' => [
                'primary' => [
                    'indexColumns' => [
                        'uuid',
                    ],
                    'isPrimary' => true,
                    'isUnique' => true,
                ],
            ],
        ],
    ]);

    expect($schema->getTables()['users']->primaryKey)->toBe('uuid');
    expect($schema->getTables()['users']->keyType)->toBe('string');
    expect($schema->getTables()['users']->incrementing)->toBe(false);
});

it('can detect primary key type from non-autoincrement int', function () {
    $schema = Schema::fromSchemaArray([
        'users' => [
            'columns' => [
                'id' => [
                    'type' => Type::bigInteger,
                ],
            ],
            'indexes' => [
                'primary' => [
                    'indexColumns' => [
                        'id',
                    ],
                    'isPrimary' => true,
                    'isUnique' => true,
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
                    'type' => Type::bigIncrements,
                ],
                'team_id' => [
                    'type' => \NovaHorizons\Realoquent\Enums\RelationshipType::belongsTo,
                    'relatedTo' => \Tests\Models\Team::class,
                ],

            ],
        ],
        'teams' => [
            'model' => \Tests\Models\Team::class,
            'columns' => [
                'id' => [
                    'type' => Type::mediumIncrements,
                ],
            ],
        ],
    ]);

    $usersTable = $schema->getTables()['users'];
    $userTeamIdCol = $usersTable->getColumns()['team_id'];

    expect($usersTable->getRelations()['team'])->toBeInstanceOf(Relation::class);
    expect($userTeamIdCol->type)->toBe(Type::mediumInteger);
})->todo(); // TODO
