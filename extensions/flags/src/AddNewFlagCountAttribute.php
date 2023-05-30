<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags;

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\User\User;

class AddNewFlagCountAttribute
{
    public function __invoke(CurrentUserSerializer $serializer, User $user): int
    {
        return $this->getNewFlagCount($user);
    }

    protected function getNewFlagCount(User $actor): int
    {
        $query = Flag::whereVisibleTo($actor);

        if ($time = $actor->read_flags_at) {
            $query->where('flags.created_at', '>', $time);
        }

        return $query->distinct()->count('flags.post_id');
    }
}
