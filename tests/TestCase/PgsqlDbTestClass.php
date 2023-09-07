<?php

namespace Tests\TestCase;

use Doctrine\DBAL\Exception;

abstract class PgsqlDbTestClass extends AbstractDatabaseTestClass
{
    /**
     * @throws \Throwable
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUpWithDatabase('rl_pgsql');
    }
}
