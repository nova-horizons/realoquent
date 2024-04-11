<?php

return [
    'rl_mysql8' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('FORWARD_MYSQL_PORT', 33306),
        'database' => env('DB_DATABASE', 'testing'),
        'username' => env('DB_USERNAME', 'sail'),
        'password' => env('DB_PASSWORD', 'password'),
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ],
    'rl_mariadbLTS' => [
        'driver' => isLaravel10() ? 'mysql' : 'mariadb',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('FORWARD_MARIADB_LTS_PORT', 33308),
        'database' => env('DB_DATABASE', 'testing'),
        'username' => env('DB_USERNAME', 'sail'),
        'password' => env('DB_PASSWORD', 'password'),
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ],
    'rl_mariadbLatest' => [
        'driver' => isLaravel10() ? 'mysql' : 'mariadb',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('FORWARD_MARIADB_LATEST_PORT', 33308),
        'database' => env('DB_DATABASE', 'testing'),
        'username' => env('DB_USERNAME', 'sail'),
        'password' => env('DB_PASSWORD', 'password'),
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ],
    'rl_pgsql16' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('FORWARD_PGSQL_PORT', 35432),
        'database' => env('DB_DATABASE', 'testing'),
        'username' => env('DB_USERNAME', 'sail'),
        'password' => env('DB_PASSWORD', 'password'),
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
    ],

    'rl_sqlite' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'foreign_key_constraints' => false,
        'prefix' => '',
    ],
];
