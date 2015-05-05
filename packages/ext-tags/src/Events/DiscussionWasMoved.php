<?php namespace Flarum\Categories\Events;

use Flarum\Core\Models\Discussion;
use Flarum\Core\Models\User;

class DiscussionWasMoved
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
     * @var integer
     */
    public $oldCategoryId;

    /**
     * @param \Flarum\Core\Models\Discussion $discussion
     * @param \Flarum\Core\Models\User $user
     * @param \Flarum\Categories\Category $oldCategory
     */
    public function __construct(Discussion $discussion, User $user, $oldCategoryId)
    {
        $this->discussion = $discussion;
        $this->user = $user;
        $this->oldCategoryId = $oldCategoryId;
    }
}
