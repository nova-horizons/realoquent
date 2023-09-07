<?php

namespace Tests;

use Tests\TestCase\AbstractDatabaseTestClass;
use Tests\TestCase\MariaDbTestClass;
use Tests\TestCase\MysqlDbTestClass;
use Tests\TestCase\PgsqlDbTestClass;
use Tests\TestCase\SqliteTestClass;

enum DbTypes
{
    case mysql;
    case mariadb;
    case pgsql;
    case sqlite;

    /**
     * @return class-string<AbstractDatabaseTestClass>
     */
    public function getTestClass(): string
    {
        return match ($this) {
            self::mysql => MysqlDbTestClass::class,
            self::mariadb => MariaDbTestClass::class,
            self::pgsql => PgsqlDbTestClass::class,
            self::sqlite => SqliteTestClass::class,
        };
    }
}
