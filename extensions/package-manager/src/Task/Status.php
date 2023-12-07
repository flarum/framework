<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Task;

enum Status: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case FAILURE = 'failure';
    case SUCCESS = 'success';
}
