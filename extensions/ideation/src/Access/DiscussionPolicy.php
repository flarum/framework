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
 * Both answers come from the actor's permissions (loaded once per request)
 * and the discussion's own columns, so asking per card on the board costs no
 * queries.
 */
class DiscussionPolicy extends AbstractPolicy
{
    public function ideationVote(User $actor, Discussion $discussion): bool
    {
        // A closed idea's count is the record of how wanted it was, so it is
        // frozen for everyone, administrators included.
        return Status::isOpen($discussion->ideation_status)
            && $actor->hasPermission('ideation.vote');
    }

    public function ideationModerate(User $actor, Discussion $discussion): bool
    {
        return $actor->hasPermission('ideation.moderate');
    }
}
