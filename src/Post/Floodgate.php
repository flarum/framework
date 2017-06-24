<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Post;

use DateTime;
use Flarum\Post\Exception\FloodingException;
use Flarum\User\User;

class Floodgate
{
    /**
     * @param User $actor
     * @throws \Flarum\Post\Exception\FloodingException
     */
    public function assertNotFlooding(User $actor)
    {
        if ($this->isFlooding($actor)) {
            throw new FloodingException;
        }
    }

    /**
     * @param User $actor
     * @return bool
     */
    public function isFlooding(User $actor)
    {
        return Post::where('user_id', $actor->id)->where('time', '>=', new DateTime('-10 seconds'))->exists();
    }
}
