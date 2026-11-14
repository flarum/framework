<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Listener;

use Flarum\Discussion\Event\Started;
use Flarum\Ideation\Service\Votes;

/**
 * Posting an idea is its author's vote for it, when they may vote on it.
 * Asked once the discussion and its tags are saved, so a tag that keeps
 * someone from voting keeps this vote from them as well.
 */
class VoteForOwnIdea
{
    public function __construct(
        protected Votes $votes
    ) {
    }

    public function handle(Started $event): void
    {
        $discussion = $event->discussion;
        $actor = $event->actor;

        if ($discussion->ideation_status !== null && $actor?->can('ideationVote', $discussion)) {
            $this->votes->set($discussion, $actor, true);
        }
    }
}
