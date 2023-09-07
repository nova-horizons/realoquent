<?php

namespace NovaHorizons\Realoquent\Exceptions;

class DuplicateIdException extends \RuntimeException
{
    public function __construct(
        public readonly string $itemType,
        public readonly string $itemName,
        public readonly ?string $id
    ) {
        parent::__construct("Duplicate realoquentId found on {$itemType}: {$itemName} ({$id})");
    }
}
