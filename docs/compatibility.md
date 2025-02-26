# Compatibility

Realoquent is tested against combinations of the following:

* PHP: 8.2, 8.3, 8.4
* Laravel: 11.x
* OS: Ubuntu Latest
* MySQL: 8.x
* MariaDB: LTS (10.x), and Latest (11.x) 
* PostgreSQL: 16.x
* SQLite: 3.x

## Known Issues

### Unsupported column types
Currently, the following Laravel migration types are not supported:

* `macAddress`
* `spatialIndex`
