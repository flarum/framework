<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search\Filter;

use Flarum\Discussion\DiscussionRepository;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
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

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $this->constrain($state->getQuery(), $state->getActor(), $negate);
    }

    protected function constrain(Builder $query, User $actor, bool $negate): void
    {
        if ($actor->exists) {
            $readIds = $this->discussions->getReadIdsQuery($actor);

            $query->where(function (Builder $query) use ($readIds, $negate, $actor) {
                if (! $negate) {
                    $query->whereNotIn('id', $readIds)->when($actor->marked_all_as_read_at, function (Builder $query) use ($actor) {
                        $query->where('last_posted_at', '>', $actor->marked_all_as_read_at);
                    });
                } else {
                    $query->whereIn('id', $readIds)->when($actor->marked_all_as_read_at, function (Builder $query) use ($actor) {
                        $query->orWhere('last_posted_at', '<=', $actor->marked_all_as_read_at);
                    });
                }
            });
        }
    }
}
