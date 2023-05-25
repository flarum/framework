<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Event;

use Flarum\User\User;

class FlarumUpdated
{
    public const GLOBAL = 'global';
    public const MINOR = 'minor';
    public const MAJOR = 'major';

    public function __construct(
        public User $actor,
        public string $type
    ) {
    }
}
