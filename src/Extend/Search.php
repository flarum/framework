<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Search\AbstractSearcher;
use Illuminate\Contracts\Container\Container;

class Search implements ExtenderInterface
{
    private $fullTextGambit;
    private $gambits = [];
    private $searcher;

    public function __construct($searcherClass)
    {
        $this->searcher = $searcherClass;
    }

    public function addGambit($gambitClass)
    {
        $this->gambits[] = $gambitClass;

        return $this;
    }

    public function setFullTextGambit($gambitClass)
    {
        $this->fullTextGambit = $gambitClass;

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
    }
}