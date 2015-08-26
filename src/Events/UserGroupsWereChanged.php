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

class UserGroupsWereChanged
{
    /**
     * The user whose groups were changed.
     *
     * @var User
     */
    public $user;

    /**
     * @var Flarum\Core\Groups\Group[]
     */
    public $oldGroups;

    /**
     * @param User $user The user whose groups were changed.
     * @param Flarum\Core\Groups\Group[] $user
     */
    public function __construct(User $user, array $oldGroups)
    {
        $this->user = $user;
        $this->oldGroups = $oldGroups;
    }
}
