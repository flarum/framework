<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The `ScopePrivatePostVisibility` event.
 */
class ScopePrivatePostVisibility
{
    /**
     * @var \Flarum\Discussion\Discussion
     */
    public $discussion;

    /**
     * @var Builder
     */
    public $query;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Flarum\Discussion\Discussion $discussion
     * @param Builder $query
     * @param User $actor
     */
    public function __construct(Discussion $discussion, Builder $query, User $actor)
    {
        $this->discussion = $discussion;
        $this->query = $query;
        $this->actor = $actor;
    }
}
