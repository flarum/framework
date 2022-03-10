<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Event;

use Flarum\Discussion\Discussion;
use Flarum\User\User;

class DiscussionWasTagged
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
     * @var array
     */
    public $oldTags;

    /**
     * @param Discussion $discussion
     * @param User $actor
     * @param \Flarum\Tags\Tag[] $oldTags
     */
    public function __construct(Discussion $discussion, User $actor, array $oldTags)
    {
        $this->discussion = $discussion;
        $this->actor = $actor;
        $this->oldTags = $oldTags;
    }
}
