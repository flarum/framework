<?php namespace Flarum\Events;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Users\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The `ScopePostVisibility` event
 */
class ScopePostVisibility
{
    /**
     * @var Discussion
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
     * @param Discussion $discussion
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
