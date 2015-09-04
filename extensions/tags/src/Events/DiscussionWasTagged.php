<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Events;

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
