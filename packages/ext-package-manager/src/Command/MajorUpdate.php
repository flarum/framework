<?php

namespace SychO\PackageManager\Command;

use Flarum\User\User;

class MajorUpdate
{
    /**
     * @var \Flarum\User\User
     */
    public $actor;

    /**
     * @var bool
     */
    public $dryRun;

    public function __construct(User $actor, bool $dryRun)
    {
        $this->actor = $actor;
        $this->dryRun = $dryRun;
    }
}
