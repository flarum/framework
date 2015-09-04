<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Core\Users\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The `ScopeEmptyDiscussionVisibility` event
 */
class ScopeEmptyDiscussionVisibility
{
    /**
     * @var Builder
     */
    public $query;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param Builder $query
     * @param User $actor
     */
    public function __construct(Builder $query, User $actor)
    {
        $this->query = $query;
        $this->actor = $actor;
    }
}
