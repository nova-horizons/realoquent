<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use NovaHorizons\Realoquent\DatabaseAnalyzer;
use NovaHorizons\Realoquent\RealoquentManager;
use NovaHorizons\Realoquent\TypeDetector;
use Tests\Exceptions\DbItemDoesNotExist;

function tableExists(string $table): bool
{
    return in_array($table, DB::connection()->getSchemaBuilder()->getTableListing());
}

/**
 * @return array<string, mixed>
 *
 * @throws DbItemDoesNotExist
 */
function getColumn(string $table, string $column): array
{
    $dbColumns = DatabaseAnalyzer::getColumns($table);
    foreach ($dbColumns as $dbColumn) {
        if ($dbColumn['name'] === $column) {
            return $dbColumn;
        }
    }

    throw new DbItemDoesNotExist("Column {$column} not found in table {$table}");
}

/**
 * @return array<string, int|null>
 *
 * @throws DbItemDoesNotExist
 */
function getColumnInfo(string $table, string $column): array
{
    return TypeDetector::getInfo(getColumn($table, $column));
}

function hasColumn(string $table, string $column): bool
{
    try {
        getColumn($table, $column);

        return true;
    } catch (DbItemDoesNotExist) {
        return false;
    }
}

/**
 * @return array<string, mixed>
 *
 * @throws DbItemDoesNotExist
 */
function getIndex(string $table, string $index): array
{
    $dbIndexes = DB::connection()->getSchemaBuilder()->getIndexes($table);
    foreach ($dbIndexes as $dbIndex) {
        if ($dbIndex['name'] === $index) {
            return $dbIndex;
        }
    }

    throw new DbItemDoesNotExist("Index {$index} not found in table {$table}");
}

function hasIndex(string $table, string $index): bool
{
    try {
        getIndex($table, $index);

        return true;
    } catch (DbItemDoesNotExist) {
        return false;
    }
}

function setupDb(string $connection): void
{
    Config::set('database.connections', require __DIR__.'/../config/database.php');
    Config::set('database.default', 'rl_'.$connection);
    DB::purge();
}

function setupDbAndSchema(string $connection): void
{
    setupDb($connection);
    if (! DatabaseAnalyzer::isSqlite()) {
        Schema::dropAllTables();
    } else {
        Schema::dropIfExists('users');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('team_list');
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
    $manager->getSchemaManager()->writeSchema($schema);
}
