<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\DialogMessage\Event;

use Flarum\Messages\DialogMessage;

class Updating
{
    public function __construct(
        public DialogMessage $message,
        public array $data
    ) {
    }
}
