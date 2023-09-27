<?php

namespace Flarum\Search;

use Flarum\Database\AbstractModel;

abstract class AbstractDriver
{
    public function __construct(
        protected array $searchers
    ) {
    }

    abstract public static function name(): string;

    public function searchers(): array
    {
        return $this->searchers;
    }
}
