<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Query\QueryCriteria;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearcher;
use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;
use Illuminate\Contracts\Container\Container;

class SimpleFlarumSearch implements ExtenderInterface
{
    private ?string $fullTextGambit = null;
    private array $gambits = [];
    private array $searchMutators = [];

    /**
     * @param class-string<AbstractSearcher> $searcher: The ::class attribute of the Searcher you are modifying.
     *                               This searcher must extend \Flarum\Search\AbstractSearcher.
     */
    public function __construct(
        private readonly string $searcher
    ) {
    }

    /**
     * Add a gambit to this searcher. Gambits are used to filter search queries.
     *
     * @param class-string<AbstractRegexGambit> $gambitClass: The ::class attribute of the gambit you are adding.
     *                             This gambit must extend \Flarum\Search\AbstractRegexGambit
     * @return self
     */
    public function addGambit(string $gambitClass): self
    {
        $this->gambits[] = $gambitClass;

        return $this;
    }

    /**
     * Set the full text gambit for this searcher. The full text gambit actually executes the search.
     *
     * @param class-string<GambitInterface> $gambitClass: The ::class attribute of the full test gambit you are adding.
     *                             This gambit must implement \Flarum\Search\GambitInterface
     * @return self
     */
    public function setFullTextGambit(string $gambitClass): self
    {
        $this->fullTextGambit = $gambitClass;

        return $this;
    }

    /**
     * Add a callback through which to run all search queries after gambits have been applied.
     *
     * @param (callable(SearchState $search, QueryCriteria $criteria): void)|class-string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - \Flarum\Search\SearchState $search
     * - \Flarum\Query\QueryCriteria $criteria
     *
     * The callback should return void.
     *
     * @return self
     */
    public function addSearchMutator(callable|string $callback): self
    {
        $this->searchMutators[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        if (! is_null($this->fullTextGambit)) {
            $container->extend('flarum.simple_search.fulltext_gambits', function ($oldFulltextGambits) {
                $oldFulltextGambits[$this->searcher] = $this->fullTextGambit;

                return $oldFulltextGambits;
            });
        }

        $container->extend('flarum.simple_search.gambits', function ($oldGambits) {
            foreach ($this->gambits as $gambit) {
                $oldGambits[$this->searcher][] = $gambit;
            }

            return $oldGambits;
        });

        $container->extend('flarum.simple_search.search_mutators', function ($oldMutators) {
            foreach ($this->searchMutators as $mutator) {
                $oldMutators[$this->searcher][] = $mutator;
            }

            return $oldMutators;
        });
    }
}
