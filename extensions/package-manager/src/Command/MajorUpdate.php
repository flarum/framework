<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Command;

use Flarum\ExtensionManager\Task\Task;
use Flarum\User\User;

class MajorUpdate extends AbstractActionCommand
{
    public function __construct(
        public User $actor,
        public bool $dryRun
    ) {
    }

    public function getOperationName(): string
    {
        return Task::UPDATE_MAJOR;
    }
}
