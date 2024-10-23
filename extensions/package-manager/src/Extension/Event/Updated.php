<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Extension\Event;

use Flarum\Extension\Extension;
use Flarum\User\User;

class Updated
{
    public function __construct(
        public User $actor,
        public Extension $extension
    ) {
    }
}
