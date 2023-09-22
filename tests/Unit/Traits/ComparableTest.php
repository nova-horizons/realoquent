<?php

use NovaHorizons\Realoquent\DataObjects\Index;
use NovaHorizons\Realoquent\Enums\IndexType;
use NovaHorizons\Realoquent\RealoquentHelpers;

it('can compare two things', function () {
    $id = RealoquentHelpers::newId();
    $thing1 = new Index(
        name: 'thing1',
        tableName: 'users',
        type: IndexType::index,
        indexColumns: ['id'],
        realoquentId: $id
    );
    $thing2 = new Index(
        name: 'thing2',
        tableName: 'users',
        type: IndexType::index,
        indexColumns: ['id'],
        realoquentId: $id
    );

    $changes = $thing1->compare($thing2);
    expect($changes)->toHaveKey('index_renamed');

});

it('fails when trying to compare different ids', function () {

    $thing1 = new Index(
        name: 'thing1',
        tableName: 'users',
        type: IndexType::index,
        indexColumns: ['id'],
        realoquentId: RealoquentHelpers::newId()
    );

    $thing2 = new Index(
        name: 'thing2',
        tableName: 'users',
        type: IndexType::index,
        indexColumns: ['id'],
        realoquentId: RealoquentHelpers::newId()
    );

    $thing1->compare($thing2);

})->throws(RuntimeException::class);
