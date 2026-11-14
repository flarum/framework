<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Search;

use Flarum\Flags\Flag;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Illuminate\Database\Eloquent\Builder;

/**
 * Posts with open flags, as a moderation queue: only flags the actor may see
 * count, so everyone else gets nothing, and moderators of some tags get only
 * those tags' posts. Unless another sort is asked for, the most recently
 * flagged come first.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class FlaggedFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'flagged';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $flagged = Flag::query()->whereVisibleTo($state->getActor())->select('flags.post_id');

        $state->getQuery()->whereIn('posts.id', $flagged, 'and', $negate);

        if (! $negate) {
            $state->setDefaultSort(function (Builder $query) {
                $query->orderByDesc(
                    Flag::query()->selectRaw('max(created_at)')->whereColumn('flags.post_id', 'posts.id')
                );
            });
        }
    }
}
