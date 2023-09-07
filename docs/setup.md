# Setup

1. Install the package via composer:

`composer require --dev nova-horizons/realoquent`

2. Publish the config file:

`php artisan vendor:publish --provider="NovaHorizons\Realoquent\RealoquentServiceProvider"`

3. Review `config/realoquent.php` and adjust as needed.


4. Run `php artisan realoquent:generate-schema` to generate your initial schema file.  
   This will examine your database schema and your Eloquent models to generate your starting `schema.php`.
   You may need to revise the detected types. For example, a UUID column may be interpreted as `ColumnType::string` from the database, but you may want to change to `ColumnType::uuid` to get more accurately generate code.   
   See [column types](schema-management/columns.md#column-types) and  [generate-schema](commands/generate-schema.md) documentation for more details.
  

5. Update your models with `php realoquent:generate-models`.  
   This will create a new base model class, and update your existing models to extend it.
   The Base Model will have the fillable/guarded/casts and PHP Docs automatically generated. These should never be manually modified.  
   All of your existing model logic will remain in the main model to keep your files clean and tidy.  
   See [Model docs](eloquent/models.md) and [generate-models](commands/generate-models.md) documentation for more details.

Head to [Usage](usage.md) to learn how to use Realoquent.
