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

class RequireExtension extends AbstractActionCommand
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

    public function getOperationName(): string
    {
        return Task::EXTENSION_INSTALL;
    }
}
