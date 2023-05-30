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
    protected array $gambits = [];

    public function __construct(
        protected GambitInterface $fulltextGambit
    ) {
    }

    public function add(GambitInterface $gambit): void
    {
        $this->gambits[] = $gambit;
    }

    /**
     * Apply gambits to a search, given a search query.
     */
    public function apply(SearchState $search, string $query): void
    {
        $query = $this->applyGambits($search, $query);

        if ($query) {
            $this->applyFulltext($search, $query);
        }
    }

    /**
     * Explode a search query into an array of bits.
     */
    protected function explode(string $query): array
    {
        return str_getcsv($query, ' ');
    }

    protected function applyGambits(SearchState $search, string $query): string
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

    protected function applyFulltext(SearchState $search, string $query): void
    {
        $search->addActiveGambit($this->fulltextGambit);
        $this->fulltextGambit->apply($search, $query);
    }
}
