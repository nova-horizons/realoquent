<?php

use Doctrine\DBAL\Schema\SchemaException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use NovaHorizons\Realoquent\RealoquentManager;

/**
 * @throws \Doctrine\DBAL\Exception
 */
function getTable(string $table): Doctrine\DBAL\Schema\Table
{
    return DB::connection()
        ->getDoctrineSchemaManager()
        ->introspectTable($table);
}

/**
 * @throws \Doctrine\DBAL\Exception
 */
function tableExists(string $table): bool
{
    return DB::connection()
        ->getDoctrineSchemaManager()
        ->tablesExist([$table]);
}

/**
 * @throws SchemaException
 * @throws \Doctrine\DBAL\Exception
 */
function getColumn(string $table, string $column): Doctrine\DBAL\Schema\Column
{
    return getTable($table)->getColumn($column);
}

/**
 * @throws \Doctrine\DBAL\Exception
 */
function hasColumn(string $table, string $column): bool
{
    return getTable($table)->hasColumn($column);
}

/**
 * @throws \Doctrine\DBAL\Exception
 * @throws SchemaException
 */
function getIndex(string $table, string $index): Doctrine\DBAL\Schema\Index
{
    return getTable($table)->getIndex($index);
}

/**
 * @throws \Doctrine\DBAL\Exception
 */
function hasIndex(string $table, string $index): bool
{
    return getTable($table)->hasIndex($index);
}

function setupDb(string $connection): void
{
    Config::set('database.connections', require __DIR__.'/../config/database.php');
    Config::set('database.default', 'rl_'.$connection);
    DB::purge();
    if (DB::connection()->getDriverName() !== 'sqlite') {
        Schema::dropAllTables();
    } else {
        Schema::dropIfExists('users');
        Schema::dropIfExists('admins');
    }
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->foreignIdFor(\Tests\Models\Team::class);
        $table->string('username', 150)->unique();
        $table->string('email');
        $table->unsignedInteger('num_visits')->default(1)->index();
        $table->index(['id', 'username'], 'users_id_username_index');
        $table->timestamps();
    });
    Schema::create('team_list', function (Blueprint $table) {
        $table->uuid('team_id')->primary();
        $table->string('name');
        $table->json('images');
        $table->json('metadata');
    });
    $manager = new RealoquentManager(realoquentConfig());
    $schema = $manager->generateSchema();
    $manager->getSchemaManager()->writeSchema($schema, $manager->getModelNamespace());
}
