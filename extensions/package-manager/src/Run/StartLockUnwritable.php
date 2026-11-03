<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

use RuntimeException;

/**
 * The start lock's file could not be made, so nothing may start.
 *
 * Not the same as the lock being held: that goes away by itself in a moment,
 * this does not until somebody makes the directory writable, so it is told
 * apart and names the directory.
 */
class StartLockUnwritable extends RuntimeException
{
    public function __construct(
        public readonly string $path,
    ) {
        parent::__construct("Cannot create the start lock in $path");
    }
}
