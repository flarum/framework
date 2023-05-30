<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Event;

use Flarum\Discussion\Discussion;
use Flarum\User\User;

class Renamed
{
    public function __construct(
        public Discussion $discussion,
        public string $oldTitle,
        public ?User $actor = null
    ) {
    }
}
