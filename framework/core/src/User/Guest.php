<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Group\Group;
use Illuminate\Database\Eloquent\Collection;

class Guest extends User
{
    /**
     * Override the ID of this user, as a guest does not have an ID.
     *
     * @var int
     */
    public int $id = 0;

    /**
     * Get the guest's group, containing only the 'guests' group model.
     */
    public function getGroupsAttribute(): Collection
    {
        if (! isset($this->attributes['groups'])) {
            $this->attributes['groups'] = $this->relations['groups'] = Group::where('id', Group::GUEST_ID)->get();
        }

        return $this->attributes['groups'];
    }

    public function isGuest(): bool
    {
        return true;
    }
}
