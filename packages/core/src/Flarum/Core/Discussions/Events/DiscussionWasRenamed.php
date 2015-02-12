<?php namespace Flarum\Core\Discussions\Events;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Users\User;

class DiscussionWasRenamed
{
    public $discussion;

    public $user;

    public $oldTitle;

    public function __construct(Discussion $discussion, User $user, $oldTitle)
    {
        $this->discussion = $discussion;
        $this->user = $user;
        $this->oldTitle = $oldTitle;
    }
}
