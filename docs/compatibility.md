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
### MariaDB
System Versioned tables are not currently supported. Due to a bug in `doctrine/dbal`, these tables are not detected.
