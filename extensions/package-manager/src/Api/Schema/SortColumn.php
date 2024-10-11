<?php

namespace Flarum\ExtensionManager\Api\Schema;

use Flarum\ExtensionManager\External\RequestWrapper;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Schema\Sort;

class SortColumn extends Sort
{
    public static function make(string $name): static
    {
        return new static($name);
    }

    public function apply(object $query, string $direction, Context $context): void
    {
        /** @var RequestWrapper $query */
        $query->withQueryParams([
            'sort' => $direction === 'desc' ? "-$this->name" : $this->name,
        ]);
    }
}
