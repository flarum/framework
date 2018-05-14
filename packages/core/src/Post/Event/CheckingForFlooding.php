<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Post\Event;

use Flarum\User\User;

class CheckingForFlooding
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @param User|null $actor
     */
    public function __construct(User $actor = null)
    {
        $this->actor = $actor;
    }
}
