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

class CheckForUpdates extends AbstractActionCommand
{
    public function __construct(
        public User $actor
    ) {
    }

    public function getOperationName(): string
    {
        return Task::UPDATE_CHECK;
    }
}
