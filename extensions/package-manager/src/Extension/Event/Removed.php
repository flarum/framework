<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Extension\Event;

use Flarum\Extension\Extension;

class Removed
{
    public function __construct(
        public Extension $extension
    ) {
    }
}
