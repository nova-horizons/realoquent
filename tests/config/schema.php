<?php

use NovaHorizons\Realoquent\Enums\Type;

return [
    'users' => [
        'realoquentId' => 'eafab77c-a011-4b09-afab-11be1bd9f28d',
        'model' => \Tests\Models\User::class,
        'columns' => [
            'id' => [
                'type' => Type::integerIncrements,
                'guarded' => true,
                'primary' => true,
                'realoquentId' => '17b4565e-4940-4719-a5e2-89729bea0793',
            ],
            'username' => [
                'type' => Type::string,
                'fillable' => true,
                'unique' => true,
                'realoquentId' => 'a328d81f-1973-4fc8-87af-9b04b8f81982',
            ],
            'email' => [
                'type' => Type::string,
                'fillable' => true,
                'realoquentId' => '206d3651-f124-42cd-8774-8fb3e8d567b8',
            ],
        ],
        'indexes' => [
            'users_id_username_index' => [
                'indexColumns' => [
                    'id',
                    'username',
                ],
                'realoquentId' => '7a43db48-2ea0-4030-8e64-bea9950f91c7',
            ],
        ],
    ],
];
