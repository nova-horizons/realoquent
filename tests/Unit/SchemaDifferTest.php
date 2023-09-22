<?php

use NovaHorizons\Realoquent\DataObjects\Column;
use NovaHorizons\Realoquent\DataObjects\Index;
use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\DataObjects\Table;
use NovaHorizons\Realoquent\Enums\ColumnType;
use NovaHorizons\Realoquent\Enums\IndexType;
use NovaHorizons\Realoquent\SchemaDiffer;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('can detect renamed table', function () {

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['admins'] = $newArray['users'];
    unset($newArray['users']);
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(2);
    expect($changes['table_renamed'])->toHaveCount(1);
    expect($changes['table_renamed']['admins']['changes'])->toHaveCount(1);
    expect($changes['table_renamed']['admins']['changes']['name']['new'])->toBe('admins');
    expect($changes['table_renamed']['admins']['changes']['name']['old'])->toBe('users');
    expect($changes['table_renamed']['admins']['state'])->toBeInstanceOf(Table::class);
    expect($changes['column_updated'])->toHaveCount(2);
    expect($changes['column_updated']['admins.id']['changes'])->toHaveCount(1);
    expect($changes['column_updated']['admins.username']['changes'])->toHaveCount(1);
});

it('can detect new table', function () {

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['admins'] = [
        'model' => 'Tests\\Models\\Admins',
        'columns' => [
            'id' => [
                'type' => ColumnType::bigIncrements,
                'cast' => 'integer',
                'guarded' => true,
            ],
        ],
    ];
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['table_new'])->toHaveCount(1);
    expect($changes['table_new']['admins'])->toBeInstanceOf(Table::class);
});

it('can detect updated table', function () {

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['model'] = 'Tests\\Models\\Admin';
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['table_updated'])->toHaveCount(1);
    expect($changes['table_updated']['users']['changes'])->toHaveCount(1);
    expect($changes['table_updated']['users']['changes']['model']['new'])->toBe('Tests\\Models\\Admin');
    expect($changes['table_updated']['users']['changes']['model']['old'])->toBe('Tests\\Models\\User');
    expect($changes['table_updated']['users']['state'])->toBeInstanceOf(Table::class);
});

it('can detected removed table', function () {
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    unset($newArray['users']);
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['table_removed'])->toHaveCount(1);
    expect($changes['table_removed']['users'])->toBeInstanceOf(Table::class);
});

it('can detect new column', function () {
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['birthdate'] = [
        'type' => ColumnType::date,
        'fillable' => true,
        'nullable' => true,
    ];
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['column_new'])->toHaveCount(1);
    expect($changes['column_new']['users.birthdate'])->toBeInstanceOf(Column::class);
});

it('can detect new column with inferred index', function () {
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['birthdate'] = [
        'type' => ColumnType::date,
        'fillable' => true,
        'index' => true,
        'nullable' => true,
    ];
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(2);
    expect($changes['column_new'])->toHaveCount(1);
    expect($changes['column_new']['users.birthdate'])->toBeInstanceOf(Column::class);
    expect($changes['index_new'])->toHaveCount(1);
    expect($changes['index_new']['users.users_birthdate_index'])->toBeInstanceOf(Index::class);
    expect($changes['index_new']['users.users_birthdate_index']->indexColumns)->toBe(['birthdate']);
});

it('can detect updated column', function () {

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['email']['nullable'] = true;
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['column_updated'])->toHaveCount(1);
    expect($changes['column_updated']['users.email']['changes'])->toHaveCount(1);
    expect($changes['column_updated']['users.email']['changes']['nullable']['new'])->toBeTrue();
    expect($changes['column_updated']['users.email']['changes']['nullable']['old'])->toBeFalse();
    expect($changes['column_updated']['users.email']['state'])->toBeInstanceOf(Column::class);
});

it('can detect multiple updates on single column', function () {

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['email']['nullable'] = true;
    $newArray['users']['columns']['email']['default'] = 'you@example.com';
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['column_updated'])->toHaveCount(1);
    expect($changes['column_updated']['users.email']['changes'])->toHaveCount(2);
    expect($changes['column_updated']['users.email']['changes']['nullable']['new'])->toBeTrue();
    expect($changes['column_updated']['users.email']['changes']['nullable']['old'])->toBeFalse();
    expect($changes['column_updated']['users.email']['changes']['default']['new'])->toBe('you@example.com');
    expect($changes['column_updated']['users.email']['changes']['default']['old'])->toBeNull();
    expect($changes['column_updated']['users.email']['state'])->toBeInstanceOf(Column::class);
});

it('can detect multiple updates on multiple columns', function () {

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['username']['default'] = 'anonymous';
    $newArray['users']['columns']['email']['nullable'] = true;
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['column_updated'])->toHaveCount(2);
    expect($changes['column_updated']['users.username']['changes'])->toHaveCount(1);
    expect($changes['column_updated']['users.username']['changes']['default']['new'])->toBe('anonymous');
    expect($changes['column_updated']['users.username']['changes']['default']['old'])->toBeNull();
    expect($changes['column_updated']['users.username']['state'])->toBeInstanceOf(Column::class);
    expect($changes['column_updated']['users.email']['changes'])->toHaveCount(1);
    expect($changes['column_updated']['users.email']['changes']['nullable']['new'])->toBeTrue();
    expect($changes['column_updated']['users.email']['changes']['nullable']['old'])->toBeFalse();
    expect($changes['column_updated']['users.email']['state'])->toBeInstanceOf(Column::class);
});

it('can detected removed column', function () {
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    unset($newArray['users']['columns']['email']);
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['column_removed'])->toHaveCount(1);
    expect($changes['column_removed']['users.email'])->toBeInstanceOf(Column::class);
});

it('can detect new explicit index', function () {
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['indexes']['idx_email_username'] = [
        'type' => IndexType::index,
        'indexColumns' => [
            'email',
            'username',
        ],
    ];
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['index_new'])->toHaveCount(1);
    expect($changes['index_new']['users.idx_email_username'])->toBeInstanceOf(Index::class);
});

it('can detect new inferred index', function () {
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['email']['unique'] = true;
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(2);
    expect($changes['index_new'])->toHaveCount(1);
    expect($changes['index_new']['users.users_email_unique'])->toBeInstanceOf(Index::class);
    expect($changes['column_updated']['users.email']['changes'])->toHaveCount(1);
    expect($changes['column_updated']['users.email']['changes'])->toHaveKey('validation');
});

it('can detect renamed index', function () {

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['indexes']['username_index'] = $newArray['users']['indexes']['users_id_username_index'];
    unset($newArray['users']['indexes']['users_id_username_index']);
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['index_renamed'])->toHaveCount(1);
    expect($changes['index_renamed'])->toHaveKey('users.username_index');
});

it('can detect updated explicit index', function () {

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['indexes']['users_id_username_index']['type'] = IndexType::unique;
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['index_updated'])->toHaveCount(1);
    expect($changes['index_updated']['users.users_id_username_index']['changes'])->toHaveCount(1);
    expect($changes['index_updated']['users.users_id_username_index']['changes']['type']['new'])->toBe(IndexType::unique);
    expect($changes['index_updated']['users.users_id_username_index']['changes']['type']['old'])->toBe(IndexType::index);
    expect($changes['index_updated']['users.users_id_username_index']['state'])->toBeInstanceOf(Index::class);
});

it('can detect updated inferred index', function () {
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['columns']['username']['unique'] = false;
    $newArray['users']['columns']['username']['index'] = true;
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(3);
    expect($changes['index_new'])->toHaveKey('users.users_username_index');
    expect($changes['index_removed'])->toHaveKey('users.users_username_unique');
    expect($changes['column_updated']['users.username']['changes'])->toHaveCount(1);
    expect($changes['column_updated']['users.username']['changes'])->toHaveKey('validation');
});

it('can detect updated index columns', function () {

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['indexes']['users_id_username_index']['indexColumns'][] = 'email';
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['index_updated'])->toHaveCount(1);
    expect($changes['index_updated']['users.users_id_username_index']['state'])->toBeInstanceOf(Index::class);
    expect($changes['index_updated']['users.users_id_username_index']['changes'])->toHaveCount(1);
    expect($changes['index_updated']['users.users_id_username_index']['changes']['indexColumns']['new'])->toBe(['id', 'username', 'email']);
    expect($changes['index_updated']['users.users_id_username_index']['changes']['indexColumns']['old'])->toBe(['id', 'username']);
});

it('can detect updated index columns and change', function () {

    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    $newArray['users']['indexes']['users_id_username_index']['type'] = IndexType::unique;
    $newArray['users']['indexes']['users_id_username_index']['indexColumns'][] = 'email';
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['index_updated'])->toHaveCount(1);
    expect($changes['index_updated']['users.users_id_username_index']['state'])->toBeInstanceOf(Index::class);
    expect($changes['index_updated']['users.users_id_username_index']['changes'])->toHaveCount(2);
    expect($changes['index_updated']['users.users_id_username_index']['changes']['indexColumns']['new'])->toBe(['id', 'username', 'email']);
    expect($changes['index_updated']['users.users_id_username_index']['changes']['indexColumns']['old'])->toBe(['id', 'username']);
    expect($changes['index_updated']['users.users_id_username_index']['changes']['type']['new'])->toBe(IndexType::unique);
    expect($changes['index_updated']['users.users_id_username_index']['changes']['type']['old'])->toBe(IndexType::index);
});

it('can detected removed explicit index', function () {
    $snapshot = Schema::fromSchemaArray(mockSchema());

    $newArray = mockSchema();
    unset($newArray['users']['indexes']['users_id_username_index']);
    $new = Schema::fromSchemaArray($newArray);

    $changes = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->changes;

    expect($changes)->toHaveCount(1);
    expect($changes['index_removed'])->toHaveCount(1);
    expect($changes['index_removed']['users.users_id_username_index'])->toBeInstanceOf(Index::class);
});

it('can validate missing ids', function () {
    $schema = mockSchema();
    unset($schema['users']['realoquentId']);
    unset($schema['users']['columns']['id']['realoquentId']);
    unset($schema['users']['indexes']['users_id_username_index']['realoquentId']);
    $schema = Schema::fromSchemaArray($schema);
    try {
        (new SchemaDiffer(currentSchema: $schema, newSchema: $schema))->getSchemaChanges();
        expect(false)->toBeTrue();
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('Table users has no realoquentId');
        expect($e->getMessage())->toContain('Column users.id has no realoquentId');
        expect($e->getMessage())->toContain('Index users.users_id_username_index has no realoquentId');
    }
});

it('can find duplicate ids', function () {
    $schema = mockSchema();
    $schema['users']['columns']['id']['realoquentId'] = $schema['users']['realoquentId'];
    $schemaObj = Schema::fromSchemaArray($schema);
    try {
        (new SchemaDiffer(currentSchema: $schemaObj, newSchema: $schemaObj))->getSchemaChanges();
        expect(false)->toBeTrue();
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('Duplicate realoquentId found on column: id ('.$schema['users']['realoquentId'].')');
    }
});
