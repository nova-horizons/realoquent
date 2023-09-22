# Compatibility

Realoquent is tested against combinations of the following:

* PHP: 8.1, 8.2, 8.3
* Laravel: 10.x
* OS: Ubuntu Latest
* MySQL: 8.x
* MariaDB: 10.x
* PostgreSQL: 14.x
* SQLite: 3.x

## Known Issues

### Unsupported column types
Currently, the following Laravel migration types are not supported:

* `geometryCollection`
* `macAddress`
* `spatialIndex`

### MariaDB
System Versioned tables are not currently supported. Due to a bug in `doctrine/dbal`, these tables are not detected.

Workaround: A fork of DBAL is available, add the following to `repositories` in `composer.json`:

```
{
    "type": "vcs",
    "url": "https://github.com/pb30/doctrine-dbal.git"
}
```
Then require the fork:
`composer require doctrine/dbal "3.6.x-dev"`
