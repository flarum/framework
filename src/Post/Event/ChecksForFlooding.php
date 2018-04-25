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

class ChecksForFlooding
{
    /**
     * @var User
     */
    public $actor;
    /**
     * @var bool|null
     */
    public $isFlooding;

    /**
     * @param User|null $actor
     * @param bool|null $isFlooding
     */
    public function __construct(User $actor = null, bool &$isFlooding = null)
    {
        $this->actor = $actor;
        $this->isFlooding = &$isFlooding;
    }
}
