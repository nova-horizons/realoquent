# Schema Management
## Columns

* [Available Properties](#available-properties)
* [Column Types](#column-types)
* [Index Shorthand](#index-shorthand)
* [Adding a column](#adding-a-column)
* [Removing a column](#removing-a-column)
* [Renaming a column](#renaming-a-column)

### Available Properties

```php
return [
    'users' => [
        'columns' => [
            'user_id' => [
                'type' => ColumnType::bigIncrements,
                'guarded' => true,
                'primary' => true,
            ],
        ],
    ],
];
```

| Property           | Description                                                                                                                         | Type                                 |
|--------------------|-------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------|
| \<array key\>      | Name of the column                                                                                                                  | `string`                             |
| `type` (required)  | Type of the column, see Column Types below                                                                                          | `ColumnType`/`RelationshipType` enum |
| `nullable`         | If column is nullable or not                                                                                                        | `bool`                               |
| `default`          | Default value for column                                                                                                            | `mixed`                              |
| `unsigned`         | If column is unsigned or not                                                                                                        | `bool`                               |
| `autoIncrement`    | If column is auto-incrementing or not                                                                                               | `bool`                               |
| `length`           |                                                                                                                                     | `int`                                |
| `precision`        |                                                                                                                                     | `int`                                |
| `scale`            |                                                                                                                                     | `int`                                |
| `fillable`         | If column is fillable in Eloquent model                                                                                             | `bool`                               |
| `guarded`          | If column is guarded in Eloquent model                                                                                              | `bool`                               |
| `cast`             | How should Eloquent cast the column. See [Eloquent Casting docs](https://laravel.com/docs/10.x/eloquent-mutators#attribute-casting) | `string`                             |
| `validation`       | List of validation rules for column. See [Validation](../eloquent/validation.md)                                                    | `array<string>`                      |
| `validationGroups` | List of validation groups for column. See [Validation](../eloquent/validation.md)                                                   | `array<string>`                      |
| `primary`          | Shorthand to set column as primary key                                                                                              | `bool`                               |
| `unique`           | Shorthand to add unique index on column                                                                                             | `bool`                               |
| `index`            | Shorthand to add index on column                                                                                                    | `bool`                               |
| `fullText`         | Shorthand to add fulltext index on column                                                                                           | `bool`                               |

### Column Types

All available types are provided in the [ColumnType](../../src/Enums/ColumnType.php) enum. The names match the names of the [Laravel migration methods](https://laravel.com/docs/10.x/migrations#available-column-types).

Laravel provides several shorthand types, like `bigIncrements` (which creates `UNSIGNED BIGINT AUTO_INCREMENT`). These are supported as well and will 
automatically set the correct column properties, so you don't need to specify `unsigned => true` or `autoIncrement => true`.

You can also use `type` to specify if it is a relationship column. See [Relationships](../eloquent/relationships.md) for more 
details. In this case, the type from the foreign key will be used for the column.

### Index Shorthand

For single-column indexes, you can use the shorthand properties on the column definition rather than create a separate [index definition](indexes.md).

The available shorthand properties are `primary`, `unique`, `fullText`, and `index`. If multiple are set, the first one will be used based on the order above.

```php 
'columns' => [
    'id' => [
        'type' => ColumnType::bigIncrements,
        'guarded' => true,
        'primary' => true, // ✅ This replaces the index definition below
    ],
],
'indexes' => [
    'primary' => [ // ❌ This can be removed
        'type' => IndexType::primary,
        'indexColumns' => [
            'id',
        ],
    ],
],
```

Note that the shorthand properties are only for convenience. If you need to customize the index or need an index on multiple columns, use the full syntax.

### Adding a column

To create a new column, add a new entry to the `columns` array in the schema definition.

```diff
return [
    'users' => [
        'columns' => [
            'user_id' => [
                'type' => ColumnType::bigIncrements,
                'guarded' => true,
                'primary' => true,
            ],
+           'name' => [
+               'type' => ColumnType::string,
+               'length' => 100,
+               'fillable' => true,
            ],
        ],
    ],
];
```

Then run the [Diff command](../commands/diff.md) to generate the migrations and models.

‼️ When adding a new column, make sure to exclude the `realoquentId` property. Realoquent will add this automatically.

### Removing a column
To delete a column, simply remove the array key from the `schema.php` file and run the [Diff command](../commands/diff.md).

### Renaming a column
To change a columns name, simply change the array key in the `schema.php` file and run the [Diff command](../commands/diff.md).

```diff
return [
    'users' => [
        'columns' => [
-            'user_id' => [
+            'id' => [
                'type' => ColumnType::bigIncrements,
                'guarded' => true,
                'primary' => true,
            ],
        ],
    ],
];
```

See [Laravel documentation](https://laravel.com/docs/10.x/migrations#renaming-columns) for which databases support column renames.
