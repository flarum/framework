<?php

namespace Flarum\Search;

use Flarum\Database\AbstractModel;

abstract class AbstractDriver
{
    public function __construct(
        /**
         * @var array<class-string<AbstractModel>, class-string<SearcherInterface>>
         */
        protected array $searchers
    ) {
    }

    abstract public static function name(): string;

    public function searchers(): array
    {
        return $this->searchers;
    }

    public function supports(string $modelClass): bool
    {
        return isset($this->searchers[$modelClass]);
    }
}
