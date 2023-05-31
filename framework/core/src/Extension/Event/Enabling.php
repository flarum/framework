<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Event;

use Flarum\Extension\Extension;

class Enabling
{
    public function __construct(
        public Extension $extension
    ) {
    }
}
