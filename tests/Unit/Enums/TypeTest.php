<?php

use Illuminate\Support\Str;
use NovaHorizons\Realoquent\Enums\Type;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

test('dbal type mapping up to date', function () {
    $reflect = new ReflectionClass(\Doctrine\DBAL\Types\Type::class);
    $types = $reflect->getConstant('BUILTIN_TYPES_MAP');

    foreach ($types as $typeClass) {
        expect(Type::fromDBAL(new $typeClass()))->toBeInstanceOf(Type::class);
    }
});

test('all types have default cast', function () {
    foreach (Type::cases() as $typeClass) {
        $typeClass->getCast(); // Will throw exception if not defined
    }
    expect(true)->toBeTrue();
});

test('all incrementing shorthand are categorized accurately', function () {
    $types = collect(Type::cases())->filter(fn (Type $type) => str_ends_with(strtolower($type->value), 'increments'));
    $types->add(Type::id);
    ray($types);
    /** @var Type $typeClass */
    foreach ($types as $typeClass) {
        expect($typeClass->isAutoIncrement())->toBeTrue();
        expect($typeClass->isUnsigned())->toBeTrue();
    }
});

test('all unsigned shorthand are categorized accurately', function () {
    $types = collect(Type::cases())->filter(fn (Type $type) => str_starts_with(strtolower($type->value), 'unsigned'));
    $types->add(Type::id);
    $types->add(Type::foreignId);
    /** @var Type $typeClass */
    foreach ($types as $typeClass) {
        expect($typeClass->isUnsigned())->toBeTrue();
    }
});


it('is up-to-date with Laravel functions', function () {

    $classInfo = (new BetterReflection())
        ->reflector()
        ->reflectClass(\Illuminate\Database\Schema\Blueprint::class);

    // Get methods in Blueprint that have a return type of ColumnDefinition
    $methods = collect($classInfo->getMethods())->filter(function ($method) {
        /** @var \PHPStan\BetterReflection\Reflection\ReflectionMethod $method */
        $constExprParser = new ConstExprParser();
        $phpDocParser = new PhpDocParser(new TypeParser($constExprParser), $constExprParser);
        $tokens = new TokenIterator((new Lexer())->tokenize($method->getDocComment()));
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
        ]))
        ->sort()
        ->values()
        ->toArray();

    $types = collect(Type::cases())->map(fn (Type $type) => $type->value)->sort()->values()->toArray();

    expect($methods)->toBe($types);

});