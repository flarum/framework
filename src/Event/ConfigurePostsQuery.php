<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Event;

use Illuminate\Database\Eloquent\Builder;

/**
 * @deprecated, with functionality removed in beta 13. The event will be removed in Beta 14. This is a breaking change, and this event no longer works. Please use the Search extender instead.
 */
class ConfigurePostsQuery
{
    /**
     * @var Builder
     */
    public $query;

    /**
     * @var array
     */
    public $filter;

    /**
     * @param Builder $query
     * @param array $filter
     */
    public function __construct(Builder $query, array $filter)
    {
        $this->query = $query;
        $this->filter = $filter;
    }
}
