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
 * @todo This whole gambits thing needs way better documentation.
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

    /**
     * Add a gambit.
     *
     * @param GambitInterface $gambit
     */
    public function add($gambit)
    {
        $this->gambits[] = $gambit;
    }

    /**
     * @deprecated Do not use. Added temporarily to provide support for ConfigureUserGambits and ConfigureDiscussionGambits until they are removed in beta 17.
     */
    public function getFullTextGambit()
    {
        return $this->fulltextGambit;
    }

    /**
     * @deprecated Do not use. Added temporarily to provide support for ConfigureUserGambits and ConfigureDiscussionGambits until they are removed in beta 17.
     */
    public function getGambits()
    {
        return $this->gambits;
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
     * Set the gambit to handle fulltext searching.
     *
     * @param GambitInterface $gambit
     */
    public function setFulltextGambit($gambit)
    {
        $this->fulltextGambit = $gambit;
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
        if (! $this->fulltextGambit) {
            return;
        }

        $search->addActiveGambit($this->fulltextGambit);
        $this->fulltextGambit->apply($search, $query);
    }
}
