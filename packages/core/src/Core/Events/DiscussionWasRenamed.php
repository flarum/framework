<?php namespace Flarum\Core\Events;

use Flarum\Core\Models\Discussion;
use Flarum\Core\Models\User;

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
