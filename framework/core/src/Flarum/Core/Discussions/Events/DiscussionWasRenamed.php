<?php namespace Flarum\Core\Discussions\Events;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Users\User;

class DiscussionWasRenamed
{
    public $discussion;

    public $user;

    public function __construct(Discussion $discussion, User $user)
    {
        $this->discussion = $discussion;
        $this->user = $user;
    }
}
