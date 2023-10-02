<?php

use Illuminate\Database\Console\ShowModelCommand;
use NovaHorizons\Realoquent\Enums\RelationshipType;

test('relationship type mapping up to date', function () {
    // TODO There's probably a better source for this
    $reflect = new ReflectionClass(ShowModelCommand::class);
    $types = $reflect->getProperty('relationMethods')->getValue(app(ShowModelCommand::class));

    foreach ($types as $type) {
        $relation = RelationshipType::fromEloquentMethod(ucfirst($type));
        expect($relation)->toBeInstanceOf(RelationshipType::class);
        expect($relation->getRelationshipFunction())->toBe($type);
    }
});

test('relationship type class mapping', function () {
    // Supported type:
    expect(RelationshipType::belongsTo->isSupported())->toBe(true);
    expect(RelationshipType::belongsTo->getReturnType())->toBe(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    // Unsupported type:
    try {
        expect(RelationshipType::morphedByMany->isSupported())->toBe(false);
        RelationshipType::morphedByMany->getReturnType();
        expect(false)->toBeTrue();
    } catch (\InvalidArgumentException $e) {
    }
});
