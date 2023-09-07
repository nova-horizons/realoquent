<?php

return [
    /*
     * The directory where the schema file will be stored
     * This schema file is where you will make changes to your database schema and model files
     */
    'schema_dir' => database_path('realoquent'),

    /*
     * The directory where the schema snapshot file will be stored
     * This snapshot file is used to compare against your schema to determine what migrations/classes should be generaged
     */
    'storage_dir' => storage_path('app/realoquent'),

    /*
     * The directory where the generated migrations will be stored
     */
    'migrations_dir' => database_path('migrations'),

    /*
     * The directory where your Eloquent models are be stored
     * Any files in this directory that extend \Illuminate\Database\Eloquent\Model will be considered a model
     */
    'model_dir' => app_path('Models'),

    /*
     * The namespace for your Eloquent models
     */
    'model_namespace' => 'App\Models',

    'features' => [
        // Should Realoquent generate migrations based on changes to your schema
        'generate_migrations' => true,

        // Should Realoquent generate models and base models from your schema
        'generate_models' => true,

        'generate_query_builders' => true,
    ],

    /*
     * If you want to run a code style fixer on any generated files, enter the command here.
     * The {file} placeholder will be replaced with the file path.
     * If {file} is not included, the command will be run on your entire project based on your config
     *
     * Examples:
     *     laravel/pint: ./vendor/bin/pint {file}
     *     friendsofphp/php-cs-fixer: ./vendor/bin/php-cs-fixer fix {file}
     */
    'cs_fixer_command' => './vendor/bin/pint {file}',

];
