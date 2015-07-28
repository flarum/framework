<?php namespace Flarum\Lock\Events;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Users\User;

class DiscussionWasUnlocked
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
