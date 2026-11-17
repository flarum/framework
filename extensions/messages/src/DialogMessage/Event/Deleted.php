<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\DialogMessage\Event;

use Flarum\Messages\DialogMessage;
use Flarum\User\User;

/**
 * A message was deleted for good, through the API. Its conversation may have
 * gone with it, and its members with that, so who they were is recorded
 * before the delete.
 */
class Deleted
{
    /**
     * @param int[] $memberIds Who could see the message before it was deleted.
     */
    public function __construct(
        public DialogMessage $message,
        public User $actor,
        public int $dialogId,
        public array $memberIds,
        public bool $dialogDeleted
    ) {
    }
}
