<?php namespace Flarum\Sticky\Events;

use Flarum\Core\Models\Discussion;
use Flarum\Core\Models\User;

class DiscussionWasUnstickied
{
    /**
     * @var \Flarum\Core\Models\Discussion
     */
    public $discussion;

    /**
     * @var \Flarum\Core\Models\User
     */
    public $user;

    /**
     * @param \Flarum\Core\Models\Discussion $discussion
     * @param \Flarum\Core\Models\User $user
     */
    public function __construct(Discussion $discussion, User $user)
    {
        $this->discussion = $discussion;
        $this->user = $user;
    }
}
