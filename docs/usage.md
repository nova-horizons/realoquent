# Usage

To make a change to your database schema or models, update the item in your `schema.php` file.

For details on making changes, see the [tables](schema-management/tables.md), [columns](schema-management/columns.md),
[indexes](schema-management/indexes.md) documentation.

Then run `php artisan realoquent:diff` to review the changes, generate the migration, and update your models.

For example, after making the following changes in `schema.php`:

```diff
  'account_number' => [
      'type' => ColumnType::string,
+     'legnth' => 100,
      'fillable' => true,
      'realoquentId' => '03d1af7e-61cb-436c-8fbe-8daef0786ea8',
  ],
+ 'business_name' => [
+     'type' => ColumnType::string,
+     'nullable' => true,
+     'fillable' => true,
+ ],
- 'city' => [
-     'type' => ColumnType::string,
-     'nullable' => true,
- ],
```

Running `realoquent:diff` will preview the changes:

```
New Column: 
  accounts.business_name
Removed Column: 
  accounts.city
Updated Column: 
  accounts.account_number: 
    length: 255 => 100
    
 Do the above changes look accurate? Ready to generate migrations? (yes/no) [yes]:
 > 
```

After confirming, it will generate and run the migration:

```php
Schema::table('accounts', function (Illuminate\Database\Schema\Blueprint $table) {
    $table->string('account_number', length: 100)->change();
});

Schema::table('accounts', function (Illuminate\Database\Schema\Blueprint $table) {
    $table->string('business_name')->nullable();
});

Schema::table('accounts', function (Illuminate\Database\Schema\Blueprint $table) {
    $table->dropColumn('city');
});
```

Then it will update the Eloquent model, including:
* `@property` PHPDocs
* Eloquent casts
* Eloquent fillable/guarded arrays
* Relationship methods
* Validation rules and methods

For more details on the model generation, see the [Models](eloquent/models.md) documentation.
