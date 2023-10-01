<?php

use NovaHorizons\Realoquent\Enums\ColumnType;
use NovaHorizons\Realoquent\Enums\IndexType;

return [
    'team_list' => [
        'realoquentId' => 'bd068644-6616-444a-b0fa-5d1112ea247f',
        'model' => \Tests\Models\Team::class,
        'columns' => [
            'team_id' => [
                'type' => ColumnType::char,
                'length' => 36,
                'guarded' => true,
                'primary' => true,
                'realoquentId' => 'e16a4b39-c8ab-44ec-abff-883019ae4537',
            ],
            'name' => [
                'type' => ColumnType::string,
                'fillable' => true,
                'validationGroups' => [
                    'create',
                ],
                'realoquentId' => '962c0b93-2934-4a12-81f0-2521f95eb395',
            ],
            'images' => [
                'type' => ColumnType::json,
                'cast' => 'array',
                'fillable' => true,
                'validationGroups' => [
                    'create',
                    'update',
                ],
                'realoquentId' => 'be0663db-4001-4ff6-9a8f-fc04843393b6',
            ],
            'metadata' => [
                'type' => ColumnType::json,
                'cast' => \Illuminate\Database\Eloquent\Casts\AsArrayObject::class,
                'guarded' => true,
                'realoquentId' => '50a63224-b48d-4f71-8184-2b18080f0bfe',
            ],
        ],
    ],
    'users' => [
        'realoquentId' => 'b5d79c27-054d-46dc-bc23-222402566f67',
        'model' => \Tests\Models\User::class,
        'columns' => [
            'id' => [
                'type' => ColumnType::bigIncrements,
                'guarded' => true,
                'primary' => true,
                'realoquentId' => '4c9083bc-f126-48f3-8e8a-0168f3af5983',
            ],
            'team_team_id' => [
                'type' => ColumnType::char,
                'length' => 36,
                'guarded' => true,
                'realoquentId' => '1399c64c-efe2-4e69-bb73-c4d460e9ae53',
            ],
            'username' => [
                'type' => ColumnType::string,
                'length' => 150,
                'guarded' => true,
                'unique' => true,
                'realoquentId' => '87be602c-7530-40e5-ab01-691ba17dea5d',
            ],
            'email' => [
                'type' => ColumnType::string,
                'guarded' => true,
                'realoquentId' => 'b43a0790-8380-4bb4-a6f6-1a9549e9944e',
            ],
            'num_visits' => [
                'type' => ColumnType::unsignedInteger,
                'default' => '1',
                'guarded' => true,
                'index' => true,
                'realoquentId' => 'ba2b92ec-12d2-480b-91c4-89fa8b5c3ed0',
            ],
            'created_at' => [
                'type' => ColumnType::timestamp,
                'nullable' => true,
                'guarded' => true,
                'realoquentId' => 'ecf96844-658b-4205-9326-b3742898fe40',
            ],
            'updated_at' => [
                'type' => ColumnType::timestamp,
                'nullable' => true,
                'guarded' => true,
                'realoquentId' => 'c0c5f392-3513-4e6e-8e8c-bfdfee50dd62',
            ],
        ],
        'indexes' => [
            'users_id_username_index' => [
                'type' => IndexType::index,
                'indexColumns' => [
                    'id',
                    'username',
                ],
                'realoquentId' => 'd34f6fa1-effb-46bd-a3f2-2fc8f04344f4',
            ],
        ],
    ],
];
