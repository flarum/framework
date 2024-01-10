<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Command;

use Flarum\ExtensionManager\Task\Task;

abstract class AbstractActionCommand
{
    /**
     * @var Task|null
     */
    public $task = null;

    /**
     * @var string|null
     */
    public $package = null;

    /**
     * @var string|null
     */
    public $extensionId = null;

    abstract public function getOperationName(): string;
}
