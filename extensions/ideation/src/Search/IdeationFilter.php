<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Search;

use Flarum\Ideation\Status;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * `filter[ideation]=planned` (or `planned,in_progress`), and `filter[ideation]=all`
 * for every idea still on the roadmap. Values are matched against the fixed
 * list, never passed through: an unknown status matches nothing.
 *
 * It narrows the ordinary discussion search, so tag permissions, private
 * discussions, hidden ideas and full-text search all apply unchanged.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class IdeationFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'ideation';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $wanted = $this->asStringArray($value);

        $statuses = in_array('all', $wanted, true)
            ? Status::SETTABLE
            : array_values(array_intersect(Status::SETTABLE, $wanted));

        $state->getQuery()->whereIn('discussions.ideation_status', $statuses, 'and', $negate);
    }
}
