<?php

use Illuminate\Support\Facades\Process;
use NovaHorizons\Realoquent\DataObjects\Schema;
use NovaHorizons\Realoquent\DataObjects\Table;
use NovaHorizons\Realoquent\Enums\ColumnType;
use NovaHorizons\Realoquent\Writer\ModelWriter;
use Tests\Models\User;
use Tests\TestCase\RealoquentTestClass;

uses(RealoquentTestClass::class);

function newModelWriter(Table $table): ModelWriter
{
    return new ModelWriter($table, trim(realoquentConfig()['model_namespace'], '\\'), realpath(realoquentConfig()['model_dir']));
}

/**
 * @throws ReflectionException
 */
function getBaseModelString(Table $table): string
{
    $writer = newModelWriter($table);
    $r = new \ReflectionMethod($writer, 'buildBaseModel');

    return $r->invoke($writer);
}

it('handles uuid primary keys', function () {
    $table = Table::fromSchemaArray('users', [
        'columns' => [
            'my_id' => [
                'type' => ColumnType::uuid,
                'primary' => true,
            ],
        ],
    ]);

    $baseModel = getBaseModelString($table);
    expect($baseModel)->toContain("\$primaryKey = 'my_id';");
    expect($baseModel)->toContain("\$keyType = 'string';");
    expect($baseModel)->toContain("use \Illuminate\Database\Eloquent\Concerns\HasUuids;");
    $this->assertStringNotContainsString("use \Illuminate\Database\Eloquent\Concerns\HasUlids;", $baseModel);
    expect($baseModel)->toContain('$incrementing = false;');
});

it('handles ulid primary keys', function () {
    $table = Table::fromSchemaArray('users', [
        'columns' => [
            'my_id' => [
                'type' => ColumnType::ulid,
                'primary' => true,
            ],
        ],
    ]);

    $baseModel = getBaseModelString($table);
    expect($baseModel)->toContain("\$primaryKey = 'my_id';");
    expect($baseModel)->toContain("\$keyType = 'string';");
    expect($baseModel)->toContain("use \Illuminate\Database\Eloquent\Concerns\HasUlids;");
    $this->assertStringNotContainsString("use \Illuminate\Database\Eloquent\Concerns\HasUuids;", $baseModel);
    expect($baseModel)->toContain('$incrementing = false;');
});

it('handles casts and types', function (ColumnType $type, ?string $cast, string $phpType) {
    $table = Table::fromSchemaArray('users', [
        'columns' => [
            'my_id' => [
                'type' => $type,
                'cast' => $cast,
            ],
        ],
    ]);

    $baseModel = getBaseModelString($table);

    if (class_exists($phpType)) {
        $phpType = class_basename($phpType);
    }

    if ($cast === null) {
        $cast = $type->getCast();
    }

    expect($baseModel)->toContain("protected \$casts = ['my_id' => '{$cast}'];");
    expect($baseModel)->toContain("@property {$phpType} \$my_id");
})->with('column-and-casts');

it('preserves an existing base class', function () {
    $table = Table::fromSchemaArray('users', [
        'model' => User::class,
        'columns' => [
            'my_id' => [
                'type' => ColumnType::ulid,
                'primary' => true,
            ],
        ],
    ]);

    $baseModel = getBaseModelString($table);
    expect($baseModel)->toContain("extends \Illuminate\Foundation\Auth\User");
});

it('can generate code that passes phpstan', function () {
    setupDbAndSchema(RL_SQLITE);
    $rootDir = __DIR__.'/../../../';

    $schema = Schema::fromSchemaArray(generatedSchema());
    $files = [];
    foreach ($schema->getTables() as $table) {
        $writer = newModelWriter($table);
        $files = array_merge($files, $writer->writeModel());
    }

    $allPassed = true;
    foreach ($files as $file) {
        expect($file)->toBeReadableFile();

        $result = Process::path($rootDir)
            ->run('vendor/bin/phpstan analyse --configuration tests/phpstan-test.neon '.escapeshellarg(realpath($file)));

        if (! $result->successful()) {
            dump($result->output() ?: $result->errorOutput());
            $allPassed = false;
        }
    }

    // Revert Models
    foreach ($files as $file) {
        if (! str_contains($file, 'BaseModels')) {
            exec('git checkout -- '.escapeshellarg($file));
        }
    }

    expect($allPassed)->toBeTrue();
});

it('preserves an existing base class on a base class', function () {})->todo(); // TODO
