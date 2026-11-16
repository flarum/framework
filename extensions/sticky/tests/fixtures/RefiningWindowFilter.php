<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\Tests\fixtures;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\RefineFilterInterface;
use Flarum\Search\Filter\WindowFilterInterface;
use Flarum\Search\SearchState;

/**
 * Implements both interfaces: it counts as a window filter.
 *
 * @implements WindowFilterInterface<DatabaseSearchState>
 * @implements RefineFilterInterface<DatabaseSearchState>
 */
class RefiningWindowFilter implements WindowFilterInterface, RefineFilterInterface
{
    public function getFilterKey(): string
    {
        return 'refiningWindow';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
    }
}
