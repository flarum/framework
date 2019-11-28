<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\User\User;

/**
 * The `PrepareUserGroups` event.
 */
class PrepareUserGroups
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var array
     */
    public $groupIds;

    /**
     * @param User $user
     * @param array $groupIds
     */
    public function __construct(User $user, array &$groupIds)
    {
        $this->user = $user;
        $this->groupIds = &$groupIds;
    }
}
