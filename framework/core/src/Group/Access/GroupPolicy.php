<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class GroupPolicy extends AbstractPolicy
{
    public function can(User $actor, string $ability): ?string
    {
        if ($actor->hasPermission('group.'.$ability)) {
            return $this->allow();
        }

        return null;
    }
}
