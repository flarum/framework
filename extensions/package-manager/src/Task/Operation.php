<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Task;

enum Operation: string
{
    case EXTENSION_INSTALL = 'extension_install';
    case EXTENSION_REMOVE = 'extension_remove';
    case EXTENSION_UPDATE = 'extension_update';
    case UPDATE_GLOBAL = 'update_global';
    case UPDATE_MINOR = 'update_minor';
    case UPDATE_MAJOR = 'update_major';
    case UPDATE_CHECK = 'update_check';
    case WHY_NOT = 'why_not';
}
