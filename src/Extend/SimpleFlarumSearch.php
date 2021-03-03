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

class SimpleFlarumSearch implements ExtenderInterface
{
    private $fullTextGambit;
    private $gambits = [];
    private $searcher;
    private $searchMutators = [];

    /**
     * @param string $searcherClass: The ::class attribute of the Searcher you are modifying.
     *                               This searcher must extend \Flarum\Search\AbstractSearcher.
     */
    public function __construct($searcherClass)
    {
        $this->searcher = $searcherClass;
    }

    /**
     * Add a gambit to this searcher. Gambits are used to filter search queries.
     *
     * @param string $gambitClass: The ::class attribute of the gambit you are adding.
     *                             This gambit must extend \Flarum\Search\AbstractRegexGambit
     */
    public function addGambit($gambitClass)
    {
        $this->gambits[] = $gambitClass;

        return $this;
    }

    /**
     * Set the full text gambit for this searcher. The full text gambit actually executes the search.
     *
     * @param string $gambitClass: The ::class attribute of the full test gambit you are adding.
     *                             This gambit must implement \Flarum\Search\GambitInterface
     */
    public function setFullTextGambit($gambitClass)
    {
        $this->fullTextGambit = $gambitClass;

        return $this;
    }

    /**
     * Add a callback through which to run all search queries after gambits have been applied.
     *
     * @param callable|string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - Flarum\Search\SearchState $search
     * - Flarum\Query\QueryCriteria $criteria
     */
    public function addSearchMutator($callback)
    {
        $this->searchMutators[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (! is_null($this->fullTextGambit)) {
            $container->resolving('flarum.simple_search.fulltext_gambits', function ($oldFulltextGambits) {
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
