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

class UpdateExtension extends AbstractActionCommand
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var string
     */
    public $updateMode;

    public function __construct(User $actor, string $extensionId, string $updateMode)
    {
        $this->actor = $actor;
        $this->extensionId = $extensionId;
        $this->updateMode = $updateMode;
    }

    public function getOperationName(): string
    {
        return Task::EXTENSION_UPDATE;
    }
}
