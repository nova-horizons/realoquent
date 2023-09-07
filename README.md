# ⚠️ This is pre-beta. 

In a Laravel application, making a database-related change can be a bit complicated... Create a migration, create a model, fill out fillable/guarded, fill out casts, add relationship methods... What if it could all be done automatically?

Realoquent defines your database and model structure in a single PHP file. Make an update to this PHP schema file and run a command and everything will be generated for you:

* Generate and run a migration
* Generate or update the model class, and populate:
  * Relationship methods
  * `$fillable`
  * `$guarded`
  * `$cast`
  * Primary key name/type
* Generate base model class to keep your models clean

### Here's an example `schema.php` file for a basic Users table
```php
return [
    'users' => [
        'model' => \App\Models\User::class,
        'columns' => [
            'id' => [
                'type' => Type::id,
                'guarded' => true,
                'primary' => true,
            ],
            'team_id' => [
                'type' => Relationship::belongsTo,
                'relatedTo' => \App\Models\Team::class,
            ],
            'username' => [
                'type' => Type::string,
                'fillable' => true,
                'unique' => true,
            ],
            'birthdate' => [
                'type' => Type::date,
                'fillable' => true,
                'nullable' => true,
            ],
            'role' => [
                'type' => Type::string,
                'fillable' => false,
                'default' => 'user',
                'index' => true,
            ],
        ],
    ],
];
```

### Setup

1. Install the package via composer:

`composer require --dev novahorizons/realoquent`

2. Publish the config file:

`php artisan vendor:publish --provider="NovaHorizons\Realoquent\RealoquentServiceProvider"`

3. Review `config/realoquent.php` and adjust as needed.  
!!!!!! TODO You may need to revise types. For example, your UUID columns will be interpreted as `Type::string` from database, but you may want to change to `Type::uuid` to generate the correct model casts
The types match the [Laravel Migration Functions](https://laravel.com/docs/10.x/migrations#available-column-types)
  
4. Run `php artisan realoquent:generate-schema` to generate your initial schema file.  
This will examine your database schema and your Eloquent models to generate your starting `schema.php`


5. Update your models with `php realoquent:generate-models`.  
This will create a new base model class, and update your existing models to extend it.
The Base Model will have the fillable/guarded/casts and PHP Docs automatically generated. These should never be manually modified.  
All of your existing model logic will remain in the main model to keep your files clean and tidy.

### Usage

To make a change to your database schema or models, update the item in your `schema.php` file.

Run `php artisan realoquent:diff` to review the changes, generate the migration, and update your models.

### Todo

- nova-horizons
- wrong types coming in from DBAL
- Snapshot needs to be in VCS? or snapshot in provider on load if doesn't exist?

- Figure out way to generate models when user want and skip when they dont
- Add support for relationships/foreign keys
- Validation? Infer based on type (nullable)? static array on model. examples of array slicing in front end for different create/edit rules
- Highlight orphaned models
- Support for `$column->default(new Expression('(JSON_ARRAY())'))`
- Support for `$index->fullText/spatialIndex`
- Support for `$table->with/withCount/preventsLazyLoading`
- Support for `$table->engine/collation/charset`
- Support for `$column->collation/charset/useCurrent/useCurrentOnUpdate`

- Generate `down` migrations
- Generate other things (Nova Resources, Form Requests, other form builders?)

### Dev Setup

1. Clone the repo
2. Run `composer install`
3. Run `./vendor/bin/sail` to start test databases
4. Run `composer quality` to run CS Fixer, Pest, and PHPStan

To include in another project, add the following to your `composer.json` file, then follow normal setup.:

```json
    "repositories": [
        {
            "type": "path",
            "url": "/path/to/realoquent"
        }
    ]
```
