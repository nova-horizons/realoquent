<?php

use NovaHorizons\Realoquent\DataObjects\Index;
use NovaHorizons\Realoquent\Enums\IndexType;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('errors when missing type', function () {
    Index::fromSchemaArray(
        name: 'test',
        schema: [
            'indexColumns' => [
                'test',
            ],
        ],
        tableName: 'test'
    );
})->throws(\InvalidArgumentException::class);

it('errors when missing indexColumns', function () {
    Index::fromSchemaArray(
        name: 'test',
        schema: [
            'type' => IndexType::unique,
            'columns' => [
                'test',
            ],
        ],
        tableName: 'test'
    );
})->throws(\InvalidArgumentException::class);
