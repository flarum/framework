<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class SearchIndex implements ExtenderInterface
{
    private array $indexers = [];

    /**
     * Register an indexer for a resource.
     *
     * @param string $resourceClass: The class of the model you are indexing.
     * @param string $indexerClass: The class of the indexer you are adding.
     *                              This indexer must implement \Flarum\Search\IndexerInterface.
     */
    public function indexer(string $resourceClass, string $indexerClass): self
    {
        $this->indexers[$resourceClass][] = $indexerClass;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        if (empty($this->indexers)) {
            return;
        }

        $container->extend('flarum.search.indexers', function (array $indexers) {
            foreach ($this->indexers as $resourceClass => $indexerClasses) {
                $indexers[$resourceClass] = array_merge(
                    $indexers[$resourceClass] ?? [],
                    $indexerClasses
                );
            }

            return $indexers;
        });
    }
}
