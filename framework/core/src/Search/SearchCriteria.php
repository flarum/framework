<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\User\User;

/**
 * Represents the criteria that will determine the entire result set of a
 * query. The limit and offset are not included because they only determine
 * which part of the entire result set will be returned.
 */
class SearchCriteria
{
    public function __construct(
        public User $actor,
        public array $filters,
        public ?int $limit = null,
        public int $offset = 0,
        /**
         * An array of sort-order pairs, where the column is the key, and the order
         * is the value. The order may be 'asc', 'desc', or an array of IDs to
         * order by.
         *
         * @var array<string, string|int[]> $sort
         */
        public ?array $sort = null,
        /**
         * Is the sort for this request the default sort from the controller?
         * If false, the current request specifies a sort.
         */
        public bool $sortIsDefault = false,
    ) {
    }

    public function isFulltext(): bool
    {
        return array_key_exists('q', $this->filters);
    }
}
