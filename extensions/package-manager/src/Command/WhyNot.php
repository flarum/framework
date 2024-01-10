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

class WhyNot extends AbstractActionCommand
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var string
     */
    public $package;

    /**
     * @var string
     */
    public $version;

    public function __construct(User $actor, string $package, string $version)
    {
        $this->actor = $actor;
        $this->package = $package;
        $this->version = $version;
    }

    public function getOperationName(): string
    {
        return Task::WHY_NOT;
    }
}
