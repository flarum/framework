<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Flarum\Search\AbstractSearcher;
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
     * - Flarum\Search\AbstractSearch $search
     * - Flarum\Search\SearchCriteria $criteria
     */
    public function addSearchMutator($callback)
    {
        $this->searchMutators[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $gambitManager = AbstractSearcher::gambitManager($this->searcher);

        if (! is_null($this->fullTextGambit)) {
            $gambitManager->setFullTextGambit($container->make($this->fullTextGambit));
        }

        foreach ($this->gambits as $gambit) {
            $gambitManager->add($container->make($gambit));
        }

        foreach ($this->searchMutators as $mutator) {
            if (is_string($mutator)) {
                $mutator = ContainerUtil::wrapCallback($mutator, $container);
            }

            AbstractSearcher::addSearchMutator($this->searcher, $mutator);
        }
    }
}
