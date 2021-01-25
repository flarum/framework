<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Event;

use Flarum\Flags\Flag;
use Flarum\User\User;

class Created
{
    /**
     * @var Flag
     */
    public $flag;

    /**
     * @var User
     */
    public $actor;

    /**
     * @var array
     */
    public $data;

    /**
     * @param Flag $flag
     * @param User $actor
     * @param array $data
     */
    public function __construct(Flag $flag, User $actor, array $data = [])
    {
        $this->flag = $flag;
        $this->actor = $actor;
        $this->data = $data;
    }
}
