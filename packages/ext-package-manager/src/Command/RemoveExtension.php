<?php

namespace SychO\PackageManager\Command;

use Flarum\User\User;

class RemoveExtension
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var string
     */
    public $extensionId;

    public function __construct(User $actor, string $extensionId)
    {
        $this->actor = $actor;
        $this->extensionId = $extensionId;
    }
}
