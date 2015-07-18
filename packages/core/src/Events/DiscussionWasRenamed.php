<?php namespace Flarum\Events;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Users\User;

class DiscussionWasRenamed
{
    /**
     * @var Discussion
     */
    public $discussion;

    /**
     * @var User
     */
    public $actor;

    /**
     * @var string
     */
    public $oldTitle;

    /**
     * @param Discussion $discussion
     * @param User $actor
     * @param string $oldTitle
     */
    public function __construct(Discussion $discussion, User $actor, $oldTitle)
    {
        $this->discussion = $discussion;
        $this->actor = $actor;
        $this->oldTitle = $oldTitle;
    }
}
