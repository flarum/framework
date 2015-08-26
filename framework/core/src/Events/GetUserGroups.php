<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Core\Users\User;

/**
 * The `GetUserGroups` event
 */
class GetUserGroups
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
