<?php

namespace NovaHorizons\Realoquent\Traits;

use NovaHorizons\Realoquent\DataObjects\Column;
use NovaHorizons\Realoquent\DataObjects\Index;
use NovaHorizons\Realoquent\DataObjects\Table;

trait Comparable
{
    /**
     * @var string[]
     */
    private array $ignoreCols = [
        'realoquentId',
        'tableName',
    ];

    /**
     * @param  Table|Column|Index|null  $other
     * @return array<string, array<string, mixed>>
     */
    public function compare(mixed $other): array
    {
        $thisItem = strtolower(class_basename($this));
        $name = trim(($this->tableName ?? '').'.'.$this->name, '.');

        if (! $other) {
            return [
                $thisItem.'_new' => [$name => $this],
            ];
        }

        if ($this->realoquentId !== $other->realoquentId) {
            throw new \RuntimeException("Cannot compare {$this->name} & {$other->name} with different realoquentIds");
        }

        /** @var array<string, string|array<string, Table|Column|Index|string>> $self */
        $self = (array) $this;
        $otherArray = (array) $other;

        $diffs = [];

        foreach ($self as $key => $val) {
            if (is_array($val)) {
                // indexColumns is an array, and is handled in SchemaDiffer
                continue;
            } elseif (! in_array($key, $this->ignoreCols)) {
                if ($val !== $otherArray[$key]) {
                    $changeKey = $thisItem.($key === 'name' ? '_renamed' : '_updated');

                    $diffs[$changeKey][$name]['state'] = $this;
                    $diffs[$changeKey][$name]['changes'][$key] = [
                        'old' => $otherArray[$key],
                        'new' => $val,
                    ];
                }

            }
        }

        return $diffs;
    }
}
