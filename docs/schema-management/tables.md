# Schema Management
## Tables

* [Available Properties](#available-properties)
* [Tables and Models](#tables-and-models)
* [Adding a table](#adding-a-table)
* [Removing a table](#removing-a-table)
* [Renaming a table](#renaming-a-table)

### Available Properties

```php
return [
    'users' => [
        'model' => \Tests\Models\User::class,
        'columns' => [
            ...
        ],
        'indexes' => [
            ....
        ],
    ],
];
```
| Property      | Description                                                            | Type                      |
|---------------|------------------------------------------------------------------------|---------------------------|
| \<array key\> | Name of the table                                                      | `string`                  |
| `model`       | The Eloquent model class to use for this table, or boolean (see below) | `Model` class / `boolean` | 
| `columns`     | An array of columns for this table. See [Columns](columns.md)          | `array`                   | 
| `indexes`     | An array of indexes for this table. See [Indexes](indexes.md)          | `array`                   | 

### Tables and Models
By default, Realoquent will generate a model class when you add a new table in your schema.

If you **do not** want a model for a table, set `model` to `false` in the table definition. You can also disable
model generation globally in your `config/realoquent.php` file.

When you first run `generate-schema`, any tables that do not have an existing model will be set to `model => false`.
If you want a model for these tables, simply set `model` to `true` and run [Diff](../commands/diff.md). If you have an existing model you can also specify the class manually and run Diff. Realoquent will then update the model
and generate a base model with the correct table name and other model properties.

See [Models](../eloquent/models.md) documentation for more information.

### Adding a table
To create a new table, simply add a new array key to the `schema.php` file. The key should be the name of the table, 
and the value should be an array with `columns` keys. Set `model` to `true` to generate a model class for this table.

‼️ When adding a new table, make sure to exclude the `realoquentId` property. Realoquent will add this automatically.

```diff
return [
    'users' => [
        'model' => \Tests\Models\User::class,
        'columns' => [
            ...
        ],
        'indexes' => [
            ....
        ],
    ],
+   'teams' => [
+       'model' => true,
+       'columns' => [
+           'id' => [
+               'type' => ColumnType::id,
+               'primary' => true,
+           ],
+       ],
+   ],
];
```

Then run the [Diff command](../commands/diff.md) to generate the migrations and models.

### Removing a table
To delete a table, simply remove the array key from the `schema.php` file and run the [Diff command](../commands/diff.md).

Note that Realoquent will not remove any models that are no longer used. You will need to manually delete these.

### Renaming a table
To change a tables name, simply change the array key in the `schema.php` file and run the [Diff command](../commands/diff.md).

```diff
return [
-    'users' => [
+    'admins' => [
        'model' => \Tests\Models\User::class,
        'columns' => [
            ...
        ],
    ],
];
```

You can choose to generate new model (by changing `model` to `true`), or you can keep the current model and Realoquent
will update the table it points to. You can then choose to manually rename the model and base model if you wish.
