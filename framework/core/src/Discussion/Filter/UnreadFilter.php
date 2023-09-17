<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Filter;

use Flarum\Discussion\DiscussionRepository;
use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

class UnreadFilter implements FilterInterface
{
    public function __construct(
        protected DiscussionRepository $discussions
    ) {
    }

    public function getFilterKey(): string
    {
        return 'unread';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $this->constrain($filterState->getQuery(), $filterState->getActor(), $negate);
    }

    protected function constrain(Builder $query, User $actor, bool $negate): void
    {
        if ($actor->exists) {
            $readIds = $this->discussions->getReadIdsQuery($actor);

            $query->where(function ($query) use ($readIds, $negate, $actor) {
                if (! $negate) {
                    $query->whereNotIn('id', $readIds)->where('last_posted_at', '>', $actor->marked_all_as_read_at ?: 0);
                } else {
                    $query->whereIn('id', $readIds)->orWhere('last_posted_at', '<=', $actor->marked_all_as_read_at ?: 0);
                }
            });
        }
    }
}
