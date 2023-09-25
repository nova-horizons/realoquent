# Eloquent & Laravel
## Validation

* [Defining Validation Rules](#defining-validation-rules)
* [Using Validation Rules](#using-validation-rules)
* [Validation Groups](#validation-groups)
* [Overriding Validation](#overriding-validation)
* [Disable Validation Generation](#disable-validation-generation)

Realoquent will generate validation rules for your models based on the column definitions in your `schema.php` file.

When you initially run [realoquent:generate-schema](../commands/generate-schema.md), validation rules will be generated for all fillable columns 
based on their type and properties.

For example, the column definition below will generate: `required|integer|min:0`

```php
'account_number' => [
    'type' => ColumnType::unsignedInteger,
    'nullable' => false,
    'fillable' => true,
],
```

⚠️ Currently Realoquent only supports `string` validation rules. If you need to use class/function-based
validation rules (like `Rules\Password::defaults()`, you will need to override the validation methods in 
your model. See [Overriding Validation](#overriding-validation) section for more.

### Defining Validation Rules
You can define and override validation rules in your `schema.php` file by adding a `validation` property to the column definition.

Validation should be an array of [Laravel validation rules](https://laravel.com/docs/10.x/validation#available-validation-rules).  
⚠️ The pipe-delimited syntax is not supported at this time.

```php
'account_number' => [
    'type' => ColumnType::string,
    'length' => 100,
    'fillable' => true,
    'validation' => ['required', 'alpha_num:ascii', 'max:100'],
],
```

As always, after changing your `schema.php`, run [realoquent:diff](../commands/diff.md) to update your models.

### Using Validation Rules
The generated BaseModel class will have a static function `getValidation()` that returns an array of validation rules for all columns.

You could use this in your controller:

```php
public function store(Request $request) {
    $validated = $request->validate(\App\Models\User::getValidation());
    ...
```

Or in a FormRequest:

```php
public function rules(): array {
    return \App\Models\User::getValidation();
}
``````

### Validation Groups
You may want to have different validation rules for different scenarios. For example, you may want to validate a different set of fields for
creating a model vs updating a model.

You can define validation groups for different scenarios by adding a `validationGroups` property to the column definition:

```php
'account_number' => [
    'type' => ColumnType::string,
    'fillable' => true,
    'validation' => ['required', 'alpha_num:ascii', 'max:100'],
    'validationGroups' => ['create'],
],
'name' => [
    'type' => ColumnType::string,
    'fillable' => true,
    'validation' => ['required', 'alpha:ascii', 'max:200'],
    'validationGroups' => ['create', 'edit'],
],
```

This will generate additional static functions in the BaseModel class for each of the validation groups using the studly name of your group.
In this example, `getValidationForCreate()` will include validation rules for `account_number` and `name`. `getValidationForEdit()` will include just `name`. 

You can then use the appropriate validation group in your controller or FormRequest:

```php
public function store(Request $request) {
    $validated = $request->validate(\App\Models\User::getValidationForCreate());
    ...
}

public function update(Request $request) {
    $validated = $request->validate(\App\Models\User::getValidationForEdit());
    ...
}
```

### Overriding Validation
If you need conditional validation or other customizations, you can override the validation methods from the BaseModel class in your model.

For example:

```php
// App\Models\User.php
public static function getValidationForCreate(): array {
    $v = parent::getValidationForCreate();
    if (auth()->user()->plan === 'Free') {
        $v['num_tickets'][] = 'max:20';
    }
    $v['password'][] = Rules\Password::defaults();
    return $v;
}
```

### Disable Validation Generation
If you don't want Realoquent to generate any validation rules/methods, update your `config/realoquent.php` and 
set `features.generate_validation` to `false`.

