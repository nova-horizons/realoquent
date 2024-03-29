<?php

use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\Enums\ColumnType;
use NovaHorizons\Realoquent\Enums\IndexType;
use NovaHorizons\Realoquent\SchemaDiffer;
use NovaHorizons\Realoquent\TypeDetector;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('can migrate renamed table', function (string $connection) {
    setupDbAndSchema($connection);
    expect(tableExists('admins'))->toBeFalse();
    expect(tableExists('users'))->toBeTrue();
    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['admins'] = $newArray['users'];
    unset($newArray['users']);
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(tableExists('admins'))->toBeTrue();
    expect(tableExists('users'))->toBeFalse();
})->with('databases');

it('can migrate new table', function (string $connection) {
    setupDbAndSchema($connection);
    expect(tableExists('admins'))->toBeFalse();
    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['admins'] = [
        'model' => 'Tests\\Models\\Admins',
        'columns' => [
            'id' => [
                'type' => ColumnType::bigIncrements,
                'primary' => true,
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
    expect(getColumn('admins', 'id')['auto_increment'])->toBeTrue();
})->with('databases');

it('can migrate new table with longhand primary key', function (string $connection) {
    setupDbAndSchema($connection);
    expect(tableExists('admins'))->toBeFalse();
    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['admins'] = [
        'model' => 'Tests\\Models\\Admins',
        'columns' => [
            'id' => [
                'type' => ColumnType::bigInteger,
                'unique' => true,
                'autoIncrement' => true,
            ],
        ],
    ];

    $new = Schema::fromSchemaArray($newArray);
    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(tableExists('admins'))->toBeTrue();
    expect(getColumn('admins', 'id')['auto_increment'])->toBeTrue();
})->with('databases');

it('can migrate updated table that needs no migration', function (string $connection) {
    setupDbAndSchema($connection);
    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['model'] = 'Tests\\Models\\Admins';

    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();

    expect($migration)->toBeEmpty();
})->with('databases');

it('can migrate new table with index', function (string $connection) {
    setupDbAndSchema($connection);
    expect(tableExists('admins'))->toBeFalse();
    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['admins'] = [
        'model' => 'Tests\\Models\\Admins',
        'columns' => [
            'id' => [
                'type' => ColumnType::bigIncrements,
                'primary' => true,
                'cast' => 'integer',
                'guarded' => true,
            ],
            'username' => [
                'type' => ColumnType::string,
                'length' => 100,
            ],
        ],
        'indexes' => [
            'admins_username_unique' => [
                'type' => IndexType::unique,
                'indexColumns' => ['username'],
            ],
        ],
    ];

    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(tableExists('admins'))->toBeTrue();
    expect(getIndex('admins', 'admins_username_unique')['unique'])->toBeTrue();
})->with('databases');

it('can migrate removed table', function (string $connection) {
    setupDbAndSchema($connection);
    expect(tableExists('users'))->toBeTrue();
    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    unset($newArray['users']);
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(tableExists('users'))->toBeFalse();
})->with('databases');

it('can migrate new column', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasColumn('users', 'birthdate'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['birthdate'] = [
        'type' => ColumnType::date,
        'nullable' => true,
        'fillable' => true,
    ];
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumn('users', 'birthdate')['name'])->toBe('birthdate');
})->with('databases');

it('can migrate new column with length', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasColumn('users', 'city'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['city'] = [
        'type' => ColumnType::string,
        'length' => 105,
    ];
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(TypeDetector::getInfo(getColumn('users', 'city'))['length'])->toBe(105);
})->with('databases-supporting-length');

it('can migrate new columns with bool defaults', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasColumn('users', 'is_active'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['is_active'] = [
        'type' => ColumnType::boolean,
        'default' => true,
    ];
    $newArray['users']['columns']['is_super_admin'] = [
        'type' => ColumnType::boolean,
        'default' => false,
    ];
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);
    // dump(getColumn('users', 'is_super_admin')['default'] .' '.boolval(getColumn('users', 'is_super_admin')['default']));

    expect(getColumn('users', 'is_active')['default'])->toBeTruthy();
    expect(getColumn('users', 'is_super_admin')['default'])->toBeFalsy();
})->with('databases');

it('can migrate new column with precision/scale', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasColumn('users', 'bill_rate'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['bill_rate'] = [
        'type' => ColumnType::decimal,
        'precision' => 6,
        'scale' => 3,
    ];
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumnInfo('users', 'bill_rate')['precision'])->toBe(6);
    expect(getColumnInfo('users', 'bill_rate')['scale'])->toBe(3);
})->with('databases-supporting-length');

it('can migrate new column with a new index', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasColumn('users', 'birthdate'))->toBeFalse();
    expect(hasIndex('users', 'birthdate_index'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['birthdate'] = [
        'type' => ColumnType::date,
        'nullable' => true,
        'fillable' => true,
    ];
    $newArray['users']['indexes']['birthdate_index'] = [
        'type' => IndexType::index,
        'indexColumns' => ['birthdate'],
    ];
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumn('users', 'birthdate')['name'])->toBe('birthdate');
    expect(getIndex('users', 'birthdate_index')['name'])->toBe('birthdate_index');
})->with('databases');

it('can migrate updated column', function (string $connection) {
    setupDbAndSchema($connection);
    expect(getColumn('users', 'email')['nullable'])->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['email']['nullable'] = true;
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumn('users', 'email')['nullable'])->toBeTrue();
})->with('databases');

it('can migrate updated column length', function (string $connection) {
    setupDbAndSchema($connection);
    expect(getColumnInfo('users', 'email')['length'])->toBe(255);

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['email']['length'] = 100;
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumnInfo('users', 'email')['length'])->toBe(100);
})->with('databases-supporting-length');

it('can migrate multiple updates on single column', function (string $connection) {
    setupDbAndSchema($connection);

    expect(getColumn('users', 'email')['nullable'])->toBeFalse();
    expect(getColumn('users', 'email')['default'])->toBeNull();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['email']['nullable'] = true;
    $newArray['users']['columns']['email']['default'] = 'you@example.com';
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumn('users', 'email')['nullable'])->toBeTrue();
    expect(getColumn('users', 'email')['default'])->toBe('you@example.com');
})->with('databases');

it('can migrate multiple updates on multiple columns', function (string $connection) {
    setupDbAndSchema($connection);
    expect(getColumn('users', 'username')['default'])->toBeNull();
    expect(getColumn('users', 'email')['nullable'])->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['username']['default'] = 'temp';
    $newArray['users']['columns']['email']['nullable'] = true;
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumn('users', 'username')['default'])->toBe('temp');
    expect(getColumn('users', 'email')['nullable'])->toBeTrue();
})->with('databases');

it('can migrate updated column that needs no migration', function (string $connection) {
    setupDbAndSchema($connection);

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['email']['fillable'] = false;
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();

    expect($migration)->toBe('');
})->with('databases');

it('can migrate renamed column', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasColumn('users', 'email'))->toBeTrue();
    expect(hasColumn('users', 'email_address'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['email_address'] = $newArray['users']['columns']['email'];
    unset($newArray['users']['columns']['email']);
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(hasColumn('users', 'email'))->toBeFalse();
    expect(hasColumn('users', 'email_address'))->toBeTrue();
})->with('databases');

it('can migrate renamed column with a new index', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasColumn('users', 'email'))->toBeTrue();
    expect(hasColumn('users', 'email_address'))->toBeFalse();
    expect(hasIndex('users', 'users_email_address_unique'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['email_address'] = $newArray['users']['columns']['email'];
    unset($newArray['users']['columns']['email']);
    $newArray['users']['indexes']['users_email_address_unique'] = [
        'type' => IndexType::unique,
        'indexColumns' => ['email_address'],
    ];
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(hasColumn('users', 'email'))->toBeFalse();
    expect(hasColumn('users', 'email_address'))->toBeTrue();
    expect(getIndex('users', 'users_email_address_unique')['unique'])->toBeTrue();
})->with('databases');

it('can migrate column with unsigned', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasColumn('users', 'user_index'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['user_index'] = ['type' => ColumnType::unsignedInteger];
    $newArray['users']['columns']['user_index2'] = ['type' => ColumnType::integer, 'unsigned' => true];
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getColumn('users', 'user_index')['unsigned'])->toBeTrue();
    expect(getColumn('users', 'user_index2')['unsigned'])->toBeTrue();
})->with('databases-supporting-unsigned');

it('can migrate removed column', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasColumn('users', 'email'))->toBeTrue();
    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    unset($newArray['users']['columns']['email']);
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(hasColumn('users', 'email'))->toBeFalse();
})->with('databases');

it('can migrate new index', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasIndex('users', 'user_email_index'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['indexes']['user_email_index'] = [
        'type' => IndexType::index,
        'indexColumns' => ['email'],
    ];
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    $index = getIndex('users', 'user_email_index');

    expect($index['name'])->toBe('user_email_index');
    expect(IndexType::fromDB($index))->toBe(IndexType::index);
})->with('databases');

it('can migrate new fulltext index', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasIndex('users', 'user_email_index'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['indexes']['user_email_index'] = [
        'type' => IndexType::fullText,
        'indexColumns' => ['email'],
    ];
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    $index = getIndex('users', 'user_email_index');

    expect($index['name'])->toBe('user_email_index');
    expect(IndexType::fromDB($index))->toBe(IndexType::fullText);
})->with('databases-supporting-fulltext');

it('can migrate renamed index', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasIndex('users', 'users_username_unique'))->toBeTrue();
    expect(hasIndex('users', 'unique_username'))->toBeFalse();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['indexes']['username_index'] = $newArray['users']['indexes']['users_id_username_index'];
    unset($newArray['users']['indexes']['users_id_username_index']);
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(hasIndex('users', 'users_id_username_index'))->toBeFalse();
    expect(hasIndex('users', 'username_index'))->toBeTrue();
})->with('databases');

it('can migrate updated index', function (string $connection) {
    setupDbAndSchema($connection);
    expect(getIndex('users', 'users_username_unique')['unique'])->toBeTrue();

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['indexes']['users_id_username_index']['type'] = IndexType::unique;
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getIndex('users', 'users_id_username_index')['unique'])->toBeTrue();
})->with('databases');

it('can migrate updated index columns', function (string $connection) {
    setupDbAndSchema($connection);
    expect(getIndex('users', 'users_id_username_index')['columns'])->toBe(['id', 'username']);

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['indexes']['users_id_username_index']['indexColumns'][] = 'email';
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getIndex('users', 'users_id_username_index')['columns'])->toBe(['id', 'username', 'email']);
})->with('databases');

it('can migrate updated index columns and change', function (string $connection) {
    setupDbAndSchema($connection);
    expect(getIndex('users', 'users_id_username_index')['unique'])->toBeFalse();
    expect(getIndex('users', 'users_id_username_index')['columns'])->toBe(['id', 'username']);

    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['indexes']['users_id_username_index']['type'] = IndexType::unique;
    $newArray['users']['indexes']['users_id_username_index']['indexColumns'][] = 'email';
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(getIndex('users', 'users_id_username_index')['unique'])->toBeTrue();
    expect(getIndex('users', 'users_id_username_index')['columns'])->toBe(['id', 'username', 'email']);
})->with('databases');

it('can migrate removed index', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasIndex('users', 'users_id_username_index'))->toBeTrue();
    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    unset($newArray['users']['indexes']['users_id_username_index']);
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(hasIndex('users', 'users_id_username_index'))->toBeFalse();
})->with('databases');

it('can migrate removed inferred index', function (string $connection) {
    setupDbAndSchema($connection);
    expect(hasIndex('users', 'users_username_unique'))->toBeTrue();
    $snapshot = Schema::fromSchemaArray(generatedSchema());

    $newArray = generatedSchema();
    $newArray['users']['columns']['username']['unique'] = false;
    $new = Schema::fromSchemaArray($newArray);

    $migration = (new SchemaDiffer(currentSchema: $snapshot, newSchema: $new))->getSchemaChanges()->getMigrationFunction();
    eval($migration);

    expect(hasIndex('users', 'users_username_unique'))->toBeFalse();
})->with('databases');
