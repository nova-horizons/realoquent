<?php

namespace Tests\TestCase;

use Doctrine\DBAL\Exception;

abstract class SqliteTestClass extends AbstractDatabaseTestClass
{
    /**
     * @throws \Throwable
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUpWithDatabase('rl_sqlite');
    }
}
