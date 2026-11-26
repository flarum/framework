<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Dialog\Event;

use Flarum\Messages\UserDialogState;
use Illuminate\Queue\SerializesModels;

/**
 * Raised once a read position has moved forward; marking every conversation
 * read moves none. Realtime queues it, hence SerializesModels.
 */
class UserRead
{
    use SerializesModels;

    /** Where the read reached, kept apart from $state, which a queue reloads as it is then. */
    public int $readMessageId;

    public function __construct(
        public UserDialogState $state,
        public int $previousMessageId = 0
    ) {
        $this->readMessageId = (int) $state->last_read_message_id;
    }
}
