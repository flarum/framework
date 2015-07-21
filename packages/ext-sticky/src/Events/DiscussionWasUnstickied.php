<?php namespace Flarum\Sticky\Events;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Users\User;

class DiscussionWasUnstickied
{
    /**
     * @var Discussion
     */
    public $discussion;

    /**
     * @var User
     */
    public $user;

    /**
     * @param Discussion $discussion
     * @param User $user
     */
    public function __construct(Discussion $discussion, User $user)
    {
        $this->discussion = $discussion;
        $this->user = $user;
    }
}
