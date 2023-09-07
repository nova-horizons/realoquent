# Schema Management
## Indexes

💡️ **Tip:** If you are creating an index on a single column, you can use the [Column Index Shorthand](columns.md#index-shorthand) syntax on the Column.

* [Available Properties](#available-properties)
* [Adding an index](#adding-an-index)
* [Removing an index](#removing-an-index)
* [Renaming an index](#renaming-an-index)

### Available Properties

```php
return [
    'users' => [
        'columns' => [
            ...
        ],
        'indexes' => [
            'users' => [
                'type' => IndexType::unique,
                'indexColumns' => [
                    'team_id',
                    'email',
                ],
            ],
        ],
        ],
    ],
];
```

| Property       | Description                         | Type             |
|----------------|-------------------------------------|------------------|
| \<array key\>  | Name of the index                   | `string`         |
| `type`         | Type of the index (see below)       | `IndexType` enum |
| `indexColumns` | Array of column names for the index | `array`          |

### Index Types

The following index types are supported in the [IndexType enum](../../src/Enums/IndexType.php).

* `IndexType::index`
* `IndexType::fullText`
* `IndexType::primary`
* `IndexType::spatialIndex` (⚠️ untested)
* `IndexType::unique`

For more details, see the [Laravel documentation](https://laravel.com/docs/8.x/migrations#creating-indexes) on index types.

### Adding an index

💡If your index is on a single column, you can use the [Column Index Shorthand](columns.md#index-shorthand) syntax on the column definition.

To create a new index, add a new entry to the `indexes` array in the schema definition.

```diff
return [
    'users' => [
        'columns' => [
           ...
        ],
        'indexes' => [
+           'users' => [
+               'type' => IndexType::unique,
+               'indexColumns' => [
+                   'team_id',
+                   'email',
+               ],
+           ],
        ],
    ],
];
```

Then run the [Diff command](../commands/diff.md) to generate the migrations.

‼️ When adding a new index, make sure to exclude the `realoquentId` property. Realoquent will add this automatically.

### Removing an index
To delete an index, simply remove the array key from the `schema.php` file and run the [Diff command](../commands/diff.md).

### Renaming an index
To change an index name, simply change the array key in the `schema.php` file and run the [Diff command](../commands/diff.md).

```diff
'indexes' => [
-   'users' => [
+   'users_unqiue_team_email' => [
        'type' => IndexType::unique,
        'indexColumns' => [
            'team_id',
            'email',
        ],
    ],
],
```

See [Laravel documentation](https://laravel.com/docs/10.x/migrations#renaming-indexes) for which databases support index renames.
