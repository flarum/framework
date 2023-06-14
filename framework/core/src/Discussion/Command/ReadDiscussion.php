<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Command;

use Flarum\User\User;

class ReadDiscussion
{
    public function __construct(
        public $discussionId,
        /** The user to mark the discussion as read for */
        public User $actor,
        /**  The number of the post to mark as read */
        public $lastReadPostNumber
    ) {
    }
}
