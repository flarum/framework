<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Command;

use Flarum\User\User;

class ReadDiscussions
{
    public function __construct(
        /** The user to mark the discussions as read for. */
        public User $actor,
        /**
         * Which discussions to mark as read, in the shape the `filter` query
         * parameter of `GET /api/discussions` takes. An empty array means every
         * discussion the actor can see.
         *
         * @var array<string, string|array>
         */
        public array $filters = []
    ) {
    }
}
