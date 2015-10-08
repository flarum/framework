<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Core\User;

class AvatarWillBeSaved
{
    /**
     * The user whose avatar will be saved.
     *
     * @var User
     */
    public $user;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * The path to the avatar that will be saved.
     *
     * @var string
     */
    public $path;

    /**
     * @param User $user The user whose avatar will be saved.
     * @param User $actor The user performing the action.
     * @param string $path The path to the avatar that will be saved.
     */
    public function __construct(User $user, User $actor, $path)
    {
        $this->user = $user;
        $this->actor = $actor;
        $this->path = $path;
    }
}
