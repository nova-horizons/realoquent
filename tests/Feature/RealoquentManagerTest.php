<?php

use NovaHorizons\Realoquent\RealoquentManager;
use NovaHorizons\Realoquent\Writer\SchemaWriter;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

it('can generate schema', function (string $connection) {
    setupDb($connection);
    $manager = new RealoquentManager(realoquentConfig());
    $schema = $manager->generateSchema();

    expect($schema->getTables())->toHaveCount(2);
    expect($schema->getTables())->toHaveKeys(['users', 'teams']);
    expect($schema->getTables()['users'])->toHaveKey('model');
})->with('databases');

it('can generate schema snapshot', function () {
    setupDb('sqlite');
    $manager = new RealoquentManager(realoquentConfig());
    $manager->generateAndWriteSchema();
    $schemaManager = $manager->getSchemaManager();
    expect(file_exists($schemaManager->getSchemaSnapshotPath()))->toBe(true);
    unlink($schemaManager->getSchemaSnapshotPath());
})->with('databases');

it('that mockSchema() matches setupDb()', function (string $connection) {
    setupDb($connection);
    $manager = new RealoquentManager(realoquentConfig());
    $freshString = (new SchemaWriter(
        schema: $manager->generateSchema(),
        schemaPath: $manager->getSchemaManager()->getSchemaPath(),
        modelNamespace: $manager->getModelNamespace()))->schemaToPhpString();
    $fresh = eval(str_replace('<?php', '', $freshString));
    $mock = mockSchema();

    // Regenerating schema will cause IDs to be different, so let's remove them
    recursive_unset($fresh, 'realoquentId');
    recursive_unset($mock, 'realoquentId');

    expect($mock)->toBe($fresh);
})->with('databases');

it('can find models', function (string $modelNamespace) {
    $config = realoquentConfig();
    $config['model_namespace'] = $modelNamespace;
    $manager = new RealoquentManager($config);
    expect($manager->getModels()->toArray())->toBe(['orphans' => '\Tests\Models\Orphan', 'teams' => '\Tests\Models\Team', 'users' => '\Tests\Models\User']);
})->with([
    '\Tests\Models',
    '\Tests\Models\\',
    'Tests\Models\\',
    'Tests\Models',
]);

it('can detect orphan models', function () {
    setupDb('sqlite');
    $manager = new RealoquentManager(realoquentConfig());
    $schema = $manager->generateSchema();
    expect($schema->getOrphanModels()->toArray())->toBe(['orphans' => '\Tests\Models\Orphan']);
});

it('can handle empty config', function () {
    $manager = new RealoquentManager([]);
    expect($manager->getModelNamespace())->toBe('App\\Models\\');
    expect($manager->getModelDir())->toEndWith('app/Models');
    expect($manager->getMigrationDir())->toEndWith('database/migrations');
    expect($manager->shouldRunCodeStyleFixer())->toBe(false);
    expect($manager->shouldGenerateMigrations())->toBe(true);
    expect($manager->shouldGenerateModels())->toBe(true);
});

it('can handle empty cs fixer', function () {
    $manager = new RealoquentManager(['cs_fixer_command' => '']);
    expect($manager->shouldRunCodeStyleFixer())->toBe(false);
});

it('can run empty cs fixer', function () {
    $manager = new RealoquentManager(['cs_fixer_command' => '']);
    $manager->runCodeStyleFixer(['/tmp/missing-file']);
    // Expect no exceptions
    expect(true)->toBeTrue();
});

it('can run cs fixer', function () {
    $manager = new RealoquentManager(['cs_fixer_command' => './vendor/bin/pint {file}']);
    $manager->runCodeStyleFixer([realoquentConfig()['schema_dir'].'/schema.php']);
    // Expect no exceptions
    expect(true)->toBeTrue();
});

/**
 * @param  array<string, mixed>  $array
 */
function recursive_unset(array &$array, string $unwanted_key): void
{
    unset($array[$unwanted_key]);
    foreach ($array as &$value) {
        if (is_array($value)) {
            recursive_unset($value, $unwanted_key);
        }
    }
}
