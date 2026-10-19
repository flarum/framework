<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Listener;

use Flarum\Ideation\Service\Votes;
use Flarum\User\Event\Deleting;

/**
 * The foreign key removes a deleted member's vote rows; this takes their
 * votes off the counts first, while the rows still say which ideas they were.
 */
class ForgetDeletedUserVotes
{
    public function __construct(
        protected Votes $votes
    ) {
    }

    public function handle(Deleting $event): void
    {
        $this->votes->forgetUser($event->user);
    }
}
