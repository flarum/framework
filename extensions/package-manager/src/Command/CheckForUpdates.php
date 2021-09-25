<?php

/**
 *
 */

namespace SychO\PackageManager\Command;

use Flarum\User\User;

class CheckForUpdates
{
    /**
     * @var \Flarum\User\User
     */
    public $actor;

    public function __construct(User $actor)
    {
        $this->actor = $actor;
    }
}
