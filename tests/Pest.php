<?php

/**
 * @return array<string, mixed>
 */
function realoquentConfig(): array
{
    return require __DIR__.'/config/realoquent.php';
}

/**
 * @return array<string, array<string, mixed>>
 */
function mockSchema(): array
{
    return require __DIR__.'/config/mockSchema.php';
}

/**
 * @return array<string, array<string, mixed>>
 */
function generatedSchema(): array
{
    return require __DIR__.'/config/schema.php';
}

function isLaravel10(): bool
{
    return version_compare(\Illuminate\Foundation\Application::VERSION, '11.0', '<');
}
