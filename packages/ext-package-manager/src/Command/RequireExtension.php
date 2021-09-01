<?php

namespace SychO\PackageManager\Command;

use Flarum\User\User;

class RequireExtension
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var string
     */
    public $package;

    public function __construct(User $actor, string $package)
    {
        $this->actor = $actor;
        $this->package = $package;
    }
}
