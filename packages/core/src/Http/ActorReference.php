<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\User\User;

class ActorReference
{
    /**
     * @var User
     */
    private $actor;

    public function setActor(User $actor)
    {
        $this->actor = $actor;
    }

    public function getActor(): User
    {
        return $this->actor;
    }
}
