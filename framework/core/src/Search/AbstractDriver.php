<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Database\AbstractModel;
use Illuminate\Contracts\Container\Container;

abstract class AbstractDriver
{
    public function __construct(
        /**
         * @var array<class-string<AbstractModel>, class-string<SearcherInterface>>
         */
        protected array $searchers,
        protected Container $container
    ) {
    }

    abstract public static function name(): string;

    public function getSearchers(): array
    {
        return $this->searchers;
    }

    public function supports(string $modelClass): bool
    {
        return isset($this->searchers[$modelClass]);
    }

    public function searcher(string $resourceClass): SearcherInterface
    {
        return $this->container->make($this->searchers[$resourceClass]);
    }
}
