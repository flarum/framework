<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use LogicException;

/**
 * @internal
 */
class GambitManager
{
    /**
     * @var array
     */
    protected $gambits = [];

    /**
     * @var GambitInterface
     */
    protected $fulltextGambit;

    public function __construct(GambitInterface $fulltextGambit)
    {
        $this->fulltextGambit = $fulltextGambit;
    }

    /**
     * Add a gambit.
     *
     * @param GambitInterface $gambit
     */
    public function add(GambitInterface $gambit)
    {
        $this->gambits[] = $gambit;
    }

    /**
     * Apply gambits to a search, given a search query.
     *
     * @param SearchState $search
     * @param string $query
     */
    public function apply(SearchState $search, $query)
    {
        $query = $this->applyGambits($search, $query);

        if ($query) {
            $this->applyFulltext($search, $query);
        }
    }

    /**
     * Explode a search query into an array of bits.
     *
     * @param string $query
     * @return array
     */
    protected function explode($query)
    {
        return str_getcsv($query, ' ');
    }

    /**
     * @param SearchState $search
     * @param string $query
     * @return string
     */
    protected function applyGambits(SearchState $search, $query)
    {
        $bits = $this->explode($query);

        if (! $bits) {
            return '';
        }

        foreach ($bits as $k => $bit) {
            foreach ($this->gambits as $gambit) {
                if (! $gambit instanceof GambitInterface) {
                    throw new LogicException(
                        'Gambit '.get_class($gambit).' does not implement '.GambitInterface::class
                    );
                }

                if ($gambit->apply($search, $bit)) {
                    $search->addActiveGambit($gambit);
                    unset($bits[$k]);
                    break;
                }
            }
        }

        return implode(' ', $bits);
    }

    /**
     * @param SearchState $search
     * @param string $query
     */
    protected function applyFulltext(SearchState $search, $query)
    {
        $search->addActiveGambit($this->fulltextGambit);
        $this->fulltextGambit->apply($search, $query);
    }
}
