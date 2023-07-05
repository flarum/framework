<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Event;

use Flarum\Discussion\Discussion;
use Flarum\Tags\Tag;
use Flarum\User\User;

class DiscussionWasTagged
{
    public function __construct(
        public Discussion $discussion,
        public User $actor,
        /** @var Tag[] */
        public array $oldTags
    ) {
    }
}
