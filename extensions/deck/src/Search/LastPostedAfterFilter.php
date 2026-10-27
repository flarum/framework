<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck\Search;

use Carbon\Carbon;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Throwable;

/**
 * Discussions with a post newer than the given time. Deck columns send the
 * newest `lastPostedAt` they're showing (a server timestamp, so client clocks
 * don't matter) to ask "anything since?" as a range on an indexed column,
 * instead of re-running their whole query.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class LastPostedAfterFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'lastPostedAfter';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        try {
            $after = Carbon::parse($this->asString($value))->utc();
        } catch (Throwable) {
            // An unreadable key must not read as "everything is new".
            $state->getQuery()->whereRaw('0 = 1');

            return;
        }

        $state->getQuery()->where('discussions.last_posted_at', $negate ? '<=' : '>', $after);
    }
}
