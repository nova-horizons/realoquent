<h1 align="center">
    ⚠️ This is pre-beta.<br/>
    Realoquent: Laravel Schema and Model Generator<br>Less Magic, More Generated Code
</h1>

<p align="center">
    <a href="https://packagist.org/packages/nova-horizons/realoquent"><img src="https://img.shields.io/packagist/v/nova-horizons/realoquent.svg" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/nova-horizons/realoquent/actions/workflows/tests.yml"><img src="https://github.com/nova-horizons/realoquent/actions/workflows/tests.yml/badge.svg" alt="tests"></a>
    <a href="https://github.com/nova-horizons/realoquent/actions/workflows/static-analysis.yml"><img src="https://github.com/nova-horizons/realoquent/actions/workflows/static-analysis.yml/badge.svg" alt="static analysis"></a>
    <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg" alt="MIT Licensed"></a>
</p>

In a Laravel application, making a database-related change can be a bit complicated... Create a migration, create a model, fill out fillable/guarded, fill out casts, write validation, add relationship methods... What if it could all be done automatically?

Realoquent defines your database and model structure in a single PHP file. Make an update to this PHP schema file and run a command and everything will be generated for you:

* Generate and run a migration
* Generate or update the model class, and populate:
    * Relationship methods
    * PHPDocs for model properties
    * `$fillable`
    * `$guarded`
    * `$cast`
    * Primary key name/type
* Generate base model class to separate this configuration from your model code
* Generate validation rules for your models
* Generate groups of validation rules for different scenarios (create, edit, etc.)

Realoquent is inspired by many of the functions of [Propel ORM](https://propelorm.org), like a single schema file, and generated base model classes with user-editable model classes. Generated code is as strongly typed as possible, and uses type hints as a fallback.
This provides a better experience in your IDE and static analysis tools without requiring additional plugins or packages.

## Table of Contents
- [Example `schema.php`](#example-schemaphp)
- [Setup](#setup)
- [Usage](#usage)
- [FAQ](#faq)
- [Todo](#todo)
- [Development Setup](#development-setup)


## Example `schema.php`

Here's an example `schema.php` file for a basic Users table

```php
return [
    'users' => [
        'model' => \App\Models\User::class,
        'columns' => [
            'id' => [
                'type' => ColumnType::id,
                'guarded' => true,
                'primary' => true,
            ],
            'team_id' => [
                'type' => RelationshipType::belongsTo,
                'relatedModel' => \App\Models\Team::class,
            ],
            'username' => [
                'type' => ColumnType::string,
                'fillable' => true,
                'unique' => true,
                'validation' => 'required|string|max:255',
                'validationGroups' => ['create']
            ],
            'birthdate' => [
                'type' => ColumnType::date,
                'fillable' => true,
                'nullable' => true,
                'validation' => 'date',
                'validationGroups' => ['create', 'edit']
            ],
            'role' => [
                'type' => ColumnType::string,
                'fillable' => false,
                'default' => 'user',
                'index' => true,
            ],
        ],
    ],
];
```

## Setup

See [Setup](docs/setup.md) documentation for details on how to get started.

## Usage

To make a change to your database schema or models, update the item in your `schema.php` file.

Then run `php artisan realoquent:diff` to review the changes, generate the migration, and update your models.

For more details, see the documentation:

* [Setup](docs/setup.md)
* [Usage](docs/usage.md)
* Schema Management
    * [Tables](docs/schema-management/tables.md)
    * [Columns](docs/schema-management/columns.md)
    * [Indexes](docs/schema-management/indexes.md)
* Eloquent & Laravel
    * [Models Overview](docs/eloquent/models.md)
    * [Relationships](docs/eloquent/relationships.md)
    * [Validation](docs/eloquent/validation.md)
* Commands
    * [diff](docs/commands/diff.md)
    * [generate-models](docs/commands/generate-models.md)
    * [generate-schema](docs/commands/generate-schema.md)

## FAQ

### Why use a PHP file to define the schema, instead of Model properties or annotations?
Using a PHP file to define the schema, as opposed to Model properties or annotations, allows for a separation 
between the database schema and the models. This means that you can have tables in your database that do not 
necessarily have corresponding models in your code. Also, having the schema in one PHP file provides a cohesive,
scannable overview of your entire schema, making it easier to comprehend and manage. Furthermore, this approach
emphasizes the principle of separation of concerns in your project. By moving the schema to a configuration file,
it ensures that your code files are reserved exclusively for your actual application logic. This improves the overall
organization and readability of your codebase.

### Why generate a base model class?
Generating a base model class is a helpful practice to organize the code better and promote cleaner models. 
The base model class contains the auto-generated code such as fillable, guarded, casts properties, validation, and PHPDocs
for model properties. The main model file then stays small tidy, containing only your custom logic. This ensures that
auto-generated code and custom code are kept separate, reducing the chance of accidental changes and making future
updates simpler and less error-prone.

### Why does everything in schema.php have a realoquentId?
Each item in schema.php having a realoquentId serves as a unique identifier. This unique identifier is used to
track the schema changes over time. When you run the realoquent:diff command, it compares these IDs to identify
which parts have been added, removed, or changed. Specifically this helps with detecting when a column/table/index
is renamed, without requiring any extra work or different behavior to rename an item. This detailed tracking
allows for the precise generation of migration files reflecting exactly what has been modified in your schema.

## Todo

Realoquent is still in progress. Here's some of the things that need to be done:

- DBAL does not return explicit correct types
- Create Snapshot on project setup
- Add support for relationships/foreign keys
- Preserve ordering of new columns and generate correct `after()` in migration
- Define array shapes for validation methods
- Generate other validation helper methods, like `validateAndCreate` or `validateAndFill`
- Support for custom casts when generating @property PHPDocs
- Support for Expressions in column defaults `default(new Expression('(JSON_ARRAY())'))`
- Support for `spatialIndex`
- Support for route binding configuration
- Support for `$column->hidden/visible`
- Support for `$table->with/withCount/preventsLazyLoading`
- Support for `$table->engine/collation/charset`
- Support for `$column->collation/charset/useCurrent/useCurrentOnUpdate`
- Generate `down` migrations
- Generate other things (Model Factories, Nova Resources, Form Requests, other form builders?)

## Development Setup

If you want to contribute changes to Realoquent:

1. Clone this repo
2. Run `composer install`
3. Run `./vendor/bin/sail up -d` to start test databases
4. Make your changes 
5. Run `composer quality` to run CS Fixer, Pest, and PHPStan

To include in another project, add the following to your `composer.json` file, then follow normal setup:

```json
"repositories": [
    {
        "type": "path",
        "url": "/path/to/your/realoquent"
    }
]
```
