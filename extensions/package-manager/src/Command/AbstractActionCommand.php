<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Command;

use Flarum\PackageManager\Task\Task;

abstract class AbstractActionCommand
{
    /**
     * @var Task|null
     */
    public $task = null;

    abstract public function getOperationName(): string;
}
