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

class RequireExtension implements BusinessCommandInterface
{
    /**
     * @var Task
     */
    public $task = null;

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

    public function getOperationName(): string
    {
        return Task::EXTENSION_INSTALL;
    }
}
