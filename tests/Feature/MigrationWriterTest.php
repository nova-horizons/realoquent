<?php

use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\Enums\Type;
use NovaHorizons\Realoquent\SchemaDiffer;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('can migrate renamed table', function (string $connection) {
    setupDb($connection);
    expect(tableExists('admins'))->toBeFalse();
    expect(tableExists('users'))->toBeTrue();
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['admins'] = $newArray['users'];
    unset($newArray['users']);
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(tableExists('admins'))->toBeTrue();
    expect(tableExists('users'))->toBeFalse();
})->with('databases');

it('can migrate new table', function (string $connection) {
    setupDb($connection);
    expect(tableExists('admins'))->toBeFalse();
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['admins'] = [
        'model' => 'Tests\\Models\\Admins',
        'columns' => [
            'id' => [
                'type' => Type::bigIncrements,
                'cast' => 'integer',
                'guarded' => true,
            ],
        ],
    ];

    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(tableExists('admins'))->toBeTrue();
    expect(tableExists('users'))->toBeTrue();
})->with('databases');

it('can migrate updated table that needs no migration', function (string $connection) {
    setupDb($connection);
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['model'] = 'Tests\\Models\\Admins';

    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();

    expect($migration)->toBeEmpty();
})->with('databases')->todo(); // TODO Need to handle case for no migration on tables

it('can migrate removed table', function (string $connection) {
    setupDb($connection);
    expect(tableExists('users'))->toBeTrue();
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    unset($newArray['users']);
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(tableExists('users'))->toBeFalse();
})->with('databases');

it('can migrate updated column', function (string $connection) {
    setupDb($connection);
    expect(getColumn('users', 'email')->getNotnull())->toBeTrue();

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['email']['nullable'] = true;
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumn('users', 'email')->getNotnull())->toBeFalse();
})->with('databases');

it('can migrate updated column length', function (string $connection) {
    setupDb($connection);
    expect(getColumn('users', 'email')->getLength())->toBe(255);

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['email']['length'] = 100;
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumn('users', 'email')->getLength())->toBe(100);
})->with('databases-supporting-length');

it('can migrate multiple updates on single column', function (string $connection) {
    setupDb($connection);

    expect(getColumn('users', 'email')->getNotnull())->toBeTrue();
    expect(getColumn('users', 'email')->getDefault())->toBeNull();

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['email']['nullable'] = true;
    $newArray['users']['columns']['email']['default'] = 'you@example.com';
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumn('users', 'email')->getNotnull())->toBeFalse();
    expect(getColumn('users', 'email')->getDefault())->toBe('you@example.com');
})->with('databases');

it('can migrate multiple updates on multiple columns', function (string $connection) {
    setupDb($connection);
    expect(getColumn('users', 'username')->getDefault())->toBeNull();
    expect(getColumn('users', 'email')->getNotnull())->toBeTrue();

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['username']['default'] = 'temp';
    $newArray['users']['columns']['email']['nullable'] = true;
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumn('users', 'username')->getDefault())->toBe('temp');
    expect(getColumn('users', 'email')->getNotnull())->toBeFalse();
})->with('databases');

it('can migrate updated column that needs no migration', function (string $connection) {
    setupDb($connection);

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['model'] = 'Tests\\Models\\Admins';
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();

    expect($migration)->toBeEmpty();
})->with('databases')->todo(); // TODO Need to handle case for no migration on columns

it('can migrate removed column', function (string $connection) {
    setupDb($connection);
    expect(hasColumn('users', 'email'))->toBeTrue();
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    unset($newArray['users']['columns']['email']);
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(hasColumn('users', 'email'))->toBeFalse();
})->with('databases');

it('can migrate renamed index', function (string $connection) {
    setupDb($connection);
    expect(hasIndex('users', 'users_username_unique'))->toBeTrue();
    expect(hasIndex('users', 'unique_username'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['indexes']['username_index'] = $newArray['users']['indexes']['users_id_username_index'];
    unset($newArray['users']['indexes']['users_id_username_index']);
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(hasIndex('users', 'users_id_username_index'))->toBeFalse();
    expect(hasIndex('users', 'username_index'))->toBeTrue();
})->with('databases');

it('can migrate updated index', function (string $connection) {
    setupDb($connection);
    expect(getIndex('users', 'users_username_unique')->isUnique())->toBeTrue();

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['indexes']['users_id_username_index']['isUnique'] = true;
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getIndex('users', 'users_id_username_index')->isUnique())->toBeTrue();
})->with('databases');

it('can migrate updated index columns', function (string $connection) {
    setupDb($connection);
    expect(getIndex('users', 'users_id_username_index')->getColumns())->toBe(['id', 'username']);

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['indexes']['users_id_username_index']['indexColumns'][] = 'email';
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getIndex('users', 'users_id_username_index')->getColumns())->toBe(['id', 'username', 'email']);
})->with('databases');

it('can migrate updated index columns and change', function (string $connection) {
    setupDb($connection);
    expect(getIndex('users', 'users_id_username_index')->isUnique())->toBeFalse();
    expect(getIndex('users', 'users_id_username_index')->getColumns())->toBe(['id', 'username']);

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['indexes']['users_id_username_index']['isUnique'] = true;
    $newArray['users']['indexes']['users_id_username_index']['indexColumns'][] = 'email';
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getIndex('users', 'users_id_username_index')->isUnique())->toBeTrue();
    expect(getIndex('users', 'users_id_username_index')->getColumns())->toBe(['id', 'username', 'email']);
})->with('databases');

it('can migrate removed index', function (string $connection) {
    setupDb($connection);
    expect(hasIndex('users', 'users_id_username_index'))->toBeTrue();
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    unset($newArray['users']['indexes']['users_id_username_index']);
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(hasIndex('users', 'users_id_username_index'))->toBeFalse();
})->with('databases');

it('can migrate removed inferred index', function (string $connection) {
    setupDb($connection);
    expect(hasIndex('users', 'users_username_unique'))->toBeTrue();
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['username']['unique'] = false;
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(hasIndex('users', 'users_username_unique'))->toBeFalse();
})->with('databases');
