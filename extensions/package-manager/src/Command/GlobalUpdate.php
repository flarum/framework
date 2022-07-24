<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Flarum\PackageManager\Task\Task;
use Flarum\User\User;

class GlobalUpdate implements BusinessCommandInterface
{
    /**
     * @var Task
     */
    public $task = null;

    /**
     * @var \Flarum\User\User
     */
    public $actor;

    public function __construct(User $actor)
    {
        $this->actor = $actor;
    }

    public function getOperationName(): string
    {
        return Task::UPDATE_GLOBAL;
    }
}
