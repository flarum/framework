<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Discussions\Commands;

use Flarum\Core\Users\User;

class ReadDiscussion
{
    /**
     * The ID of the discussion to mark as read.
     *
     * @var integer
     */
    public $discussionId;

    /**
     * The user to mark the discussion as read for.
     *
     * @var User
     */
    public $actor;

    /**
     * The number of the post to mark as read.
     *
     * @var integer
     */
    public $readNumber;

    /**
     * @param integer $discussionId The ID of the discussion to mark as read.
     * @param User $actor The user to mark the discussion as read for.
     * @param integer $readNumber The number of the post to mark as read.
     */
    public function __construct($discussionId, User $actor, $readNumber)
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->readNumber = $readNumber;
    }
}
