<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Lock\Access;

use Flarum\Discussion\Discussion;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class DiscussionPolicy extends AbstractPolicy
{
    public function reply(User $actor, Discussion $discussion): ?string
    {
        if ($discussion->is_locked && $actor->cannot('lock', $discussion)) {
            return $this->deny();
        }

        return null;
    }
}
