<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Event;

use Flarum\Discussion\Discussion;
use Flarum\User\User;

/**
 * An idea moved. `$new` is null when a moderator took a discussion off the
 * roadmap, and `$mergedInto` is set when it was folded into another idea.
 * `$via` names the GitHub item when the move was automatic.
 */
class StatusChanged
{
    public function __construct(
        public Discussion $discussion,
        public User $actor,
        public ?string $old,
        public ?string $new,
        public ?Discussion $mergedInto = null,
        public ?string $via = null
    ) {
    }
}
