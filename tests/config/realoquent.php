<?php

return [
    'schema_dir' => __DIR__,
    'storage_dir' => '/tmp/realoquent/storage',
    'migrations_dir' => '/tmp/realoquent/migrations',
    'model_dir' => __DIR__.'/../Models',
    'model_namespace' => '\Tests\Models',

    'features' => [
        'generate_migrations' => true,
        'generate_models' => true,
        'generate_query_builders' => true,
    ],

];
