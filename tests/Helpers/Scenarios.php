<?php

use NovaHorizons\Realoquent\RealoquentManager;

function causeDuplicateIds(RealoquentManager $manager): void
{
    $schemaPath = $manager->getSchemaManager()->getSchemaPath();
    $schema = file_get_contents($schemaPath);
    // Replace IDs with the same
    $pattern = "/'realoquentId'\s*=>\s*'(.*)?',/";
    $replacement = "'realoquentId' => '00000000-0000-0000-0000-000000000000',";

    $newSchema = preg_replace($pattern, $replacement, $schema);
    file_put_contents($schemaPath, $newSchema);
}

function modifySchema(RealoquentManager $manager, callable $modify): void
{
    $schemaPath = $manager->getSchemaManager()->getSchemaPath();
    $schema = file_get_contents($schemaPath);
    $newSchema = $modify($schema);
    file_put_contents($schemaPath, $newSchema);
}
