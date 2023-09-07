<?php

namespace Tests\TestCase;

use Doctrine\DBAL\Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use NovaHorizons\Realoquent\RealoquentManager;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as TestBenchTestCase;
use Throwable;

abstract class AbstractDatabaseTestClass extends TestBenchTestCase
{
    use WithWorkbench;

    /**
     * @throws Throwable
     * @throws Exception
     */
    protected function setUpWithDatabase(string $database): void
    {
        parent::setUp();
        Config::set('database.connections', require __DIR__.'/../config/database.php');
        Config::set('database.default', $database);
        DB::purge();
        $this->migrate();
        $this->setupRealoquent();
    }

    protected function migrate(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('admins');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 150)->unique();
            $table->string('email');
            $table->index(['id', 'username'], 'users_id_username_index');
        });
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    protected function setupRealoquent(): void
    {
        $manager = new RealoquentManager(realoquentConfig());
        $schema = $manager->generateSchema();
        $manager->getSchemaManager()->writeSchema($schema, $manager->getModelNamespace());
    }
}
