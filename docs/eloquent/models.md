# Eloquent & Laravel
## Model Overview

Realoquent can optionally generate and maintain Eloquent models for your tables. When you run the [realoquent:diff](../commands/diff.md) 
or [realoquent:generate-models](../commands/generate-models.md) commands, Realoquent will generate or update your models
based on the `schema.php` definition.

For this documentation, we'll use this example `schema.php` file:

```php
return [
    'users' => [
        'model' => \App\Models\User::class,
        'columns' => [
            'id' => [
                'type' => ColumnType::bigIncrements,
                'guarded' => true,
                'primary' => true,
            ],
            'team_id' => [
                'type' => RelationshipType::belongsTo,
                'relatedModel' => \App\Models\Team::class,
                'relationName' => 'team',
                'fillable' => true,
            ],
            'username' => [
                'type' => ColumnType::string,
                'length' => 150,
                'fillable' => true,
                'unique' => true,
            ],
            'email' => [
                'type' => ColumnType::string,
                'fillable' => true,
                'validationGroups' => ['edit'],
            ],
            'birthdate' => [
                'type' => ColumnType::date,
                'nullable' => true,
                'fillable' => true,
                'index' => true,
                'validationGroups' => ['edit'],
            ],
        ],
    ],
];
```

### Initial Project Setup
During the initial setup when you run `realoquent:generate-schema`, Realoquent will use two sources to build the initial schema:
1. Doctrine DBAL to get your full database schema
2. Your existing Eloquent models

Realoquent will use your existing models to build the schema with things like fillable/guarded/casts and relationships. It will
preserve all of your existing model configuration and represent it in the schema. Going forward, that is the source of truth for configuring 
your models.

### Base Classes
When generating models, Realoquent will first create a new abstract Base Model class for each table, in this case `\App\Models\BaseModels\BaseUser`. This class
will have all the generated code and **should not be manually edited**. The base model class will extend the default Laravel Eloquent Model
class (or whatever class your model was extending previously).

Realoquent will then update the main model (`\App\Models\User`) to extend the new base model class. It will also move any of the things it generates 
from the model class to the base model class. This leaves the `\App\Models\User` clean of any database/Eloquent "configuration" and allows you to
add any of your custom business logic to the model.

Realoquent will generate the following items in each base model class:

* `@property` PHPDoc annotations for each column including type info 
  * Ex: `@property ?Carbon $birthdate`
* Relationship methods for any related columns
* `$fillable` array with all fillable columns
* `$guarded` array with any guarded columns
* `$casts` array with any columns that should be casted
  * Ex: `$casts = ['id' => 'integer', 'birthdate' => 'date', ...];`
* Validation rules for each column (based on type, or explicitly defined in the schema)
  * Ex: `'username' => 'required|string|max:150|unique:users,username',`
* Optionally `getValidationGroups()` method to retrieve rules for specific sets of columns (see below)
* Database configuration info for the model
  * `$table` name
  * `$primaryKey` name
  * `$keyType` type
  * `$incrementing` boolean
* `@mixin Builder` PHPDoc to help IDEs/analyzers with other Eloquent query method autocompletion

Here's an example of the generated base model class for our example `users` table:

```php
namespace App\Models\BaseModels;

/**
 * @property integer $id
 * ....
 * @property ?Carbon $birthdate
 * @mixin Builder<\App\Models\User>
 */
abstract class BaseUser extends Model
{
    /** @var string */
    protected $table = 'users';

    /** @var string */
    protected $primaryKey = 'id';

    /** @var string */
    protected $keyType = 'integer';

    /** @var bool */
    public $incrementing = true;

    /** @var string[] */
    protected $fillable = ['team_id', 'username', 'email', 'birthdate'];

    /** @var string[] */
    protected $guarded = ['*'];

    /** @var array<string, string> */
    protected $casts = [
        'id' => 'integer',
        'team_id' => 'integer',
        'username' => 'string',
        'email' => 'string',
        'birthdate' => 'date',
    ];

    /** @var array<string, string[]> */
    protected static array $validation = [
        'id' => ['required', 'integer', 'min:0'],
        'team_id' => ['required', 'integer', 'min:0'],
        'username' => ['required', 'max:150'],
        'email' => ['required', 'max:255'],
        'birthdate' => ['date'],
    ];

    /** @var array<string, string[]> */
    protected static array $validationGroups = ['edit' => ['email', 'birthdate']];

    /**
     * @return array<string, string[]>
     */
    public static function getValidation(): array
    {
        return self::$validation;
    }
    
    /**
     * @return array<string, string[]>
     */
    public static function getValidationForEdit(): array
    {
        return array_intersect_key(self::$validation, array_flip(self::$validationGroups['edit']));
    }

    /**
     * @return BelongsTo<Account, self>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

```

### Relationships
See [Relationships](validation.md) documentation for details on how to define relationships in your schema.

### Validation
See [Validation](validation.md) documentation for details on the generated validation rules and methods.
