<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\Tests\fixtures;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\WindowFilterInterface;
use Flarum\Search\SearchState;

/**
 * Stands in for Deck's activity check (`lastPostedAfter`): a window of a list.
 *
 * @implements WindowFilterInterface<DatabaseSearchState>
 */
class WindowFilter implements WindowFilterInterface
{
    public function getFilterKey(): string
    {
        return 'window';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
    }
}
