<?php namespace Flarum\Tags\Events;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Users\User;

class DiscussionWasTagged
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
     * @var array
     */
    public $oldTags;

    /**
     * @param Discussion $discussion
     * @param User $user
     * @param \Flarum\Tags\Tag[] $oldTags
     */
    public function __construct(Discussion $discussion, User $user, array $oldTags)
    {
        $this->discussion = $discussion;
        $this->user = $user;
        $this->oldTags = $oldTags;
    }
}
