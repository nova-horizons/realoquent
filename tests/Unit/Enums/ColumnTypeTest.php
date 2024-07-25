<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use NovaHorizons\Realoquent\Enums\ColumnType;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Roave\BetterReflection\BetterReflection;

test('all types have default cast', function () {
    foreach (ColumnType::cases() as $typeClass) {
        $typeClass->getCast(); // Will throw exception if not defined
    }
    expect(true)->toBeTrue();
});

test('all incrementing shorthand are categorized accurately', function () {
    $types = collect(ColumnType::cases())->filter(fn (ColumnType $type) => str_ends_with(strtolower($type->value), 'increments'));
    $types->add(ColumnType::id);
    /** @var ColumnType $typeClass */
    foreach ($types as $typeClass) {
        expect($typeClass->isAutoIncrement())->toBeTrue();
        expect($typeClass->isUnsigned())->toBeTrue();
    }
});

test('all unsigned shorthand are categorized accurately', function () {
    $types = collect(ColumnType::cases())->filter(fn (ColumnType $type) => str_starts_with(strtolower($type->value), 'unsigned'));
    $types->add(ColumnType::id);
    $types->add(ColumnType::foreignId);
    /** @var ColumnType $typeClass */
    foreach ($types as $typeClass) {
        expect($typeClass->isUnsigned())->toBeTrue();
    }
});

it('is up-to-date with Laravel functions', function () {
    $classInfo = (new BetterReflection)
        ->reflector()
        ->reflectClass(Blueprint::class);

    // Get methods in Blueprint that have a return type of ColumnDefinition
    $methods = collect($classInfo->getMethods())->filter(function ($method) {
        /** @var \Roave\BetterReflection\Reflection\ReflectionMethod $method */
        $constExprParser = new ConstExprParser;
        $phpDocParser = new PhpDocParser(new TypeParser($constExprParser), $constExprParser);
        if (! $method->getDocComment()) {
            return false;
        }
        $tokens = new TokenIterator((new Lexer)->tokenize($method->getDocComment()));
        $phpDocNode = $phpDocParser->parse($tokens);
        foreach ($phpDocNode->children as $child) {
            if (property_exists($child, 'name') && $child->name === '@return') {
                $name = $child->value->type->name ?? '';
                if (Str::endsWith($name, 'ColumnDefinition')) {
                    return true;
                }
            }
        }

        return false;
    })->map->getName()
        ->filter(fn (string $name) => ! in_array($name, [
            // Filter out some methods that we know aren't column definitions
            'addColumn',
            'addColumnDefinition',
            'computed',
            'macAddress', // TODO-macAddress Breaking pgsql tests
        ]))
        ->sort()
        ->values()
        ->toArray();

    $types = collect(ColumnType::cases())->map(fn (ColumnType $type) => $type->value)->sort()->values()->toArray();

    expect($methods)->toBe($types);

})->skip(isLaravel10(), 'Only for latest version of Laravel');
