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
