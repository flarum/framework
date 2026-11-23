<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class DiscussionFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'discussion';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $ids = array_map(
            fn (string $id) => (int) $id, // SQLite's json_extract() yields an INTEGER, which a string binding never equals
            $this->asStringArray($value)
        );

        $query = $state->getQuery();

        if ($negate) {
            // Keep entries about no discussion: NULL NOT IN (...) is never true.
            $query->where(fn ($query) => $query->whereNull('payload->discussion_id')->orWhereNotIn('payload->discussion_id', $ids));
        } else {
            $query->whereIn('payload->discussion_id', $ids);
        }
    }
}
