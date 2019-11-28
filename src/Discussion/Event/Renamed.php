<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Event;

use Flarum\Discussion\Discussion;
use Flarum\User\User;

class Renamed
{
    /**
     * @var Discussion
     */
    public $discussion;

    /**
     * @var string
     */
    public $oldTitle;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Flarum\Discussion\Discussion $discussion
     * @param User $actor
     * @param string $oldTitle
     */
    public function __construct(Discussion $discussion, $oldTitle, User $actor = null)
    {
        $this->discussion = $discussion;
        $this->oldTitle = $oldTitle;
        $this->actor = $actor;
    }
}
