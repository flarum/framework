<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Access;

use Flarum\Discussion\Discussion;
use Flarum\Ideation\Status;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

/**
 * Voting and moderating are the permissions `discussion.ideationVote` and
 * `discussion.ideationModerate`, granted by core's discussion policy like any
 * other `discussion.*` ability, so the tags extension can scope them to a
 * restricted tag (`tag{id}.discussion.ideationVote`).
 *
 * The answers come from the actor's permissions (loaded once per request)
 * and the discussion's own columns, so asking per card on the board costs no
 * queries.
 */
class DiscussionPolicy extends AbstractPolicy
{
    public function ideationVote(User $actor, Discussion $discussion): ?string
    {
        // A closed idea's count is the record of how wanted it was, so it is
        // frozen for everyone, administrators included.
        if (! Status::isOpen($discussion->ideation_status)) {
            return $this->deny();
        }

        return null;
    }
}
