<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail\Event;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Fired when a mail driver reports that a message to an address permanently
 * failed to deliver (a hard bounce). Transient/soft failures do NOT fire this.
 */
class EmailBounced
{
    public function __construct(
        public readonly string $email,
        public readonly string $reason,
        /** @var Collection<int, User> Every account that uses the address; empty when none does. */
        public readonly Collection $recipients = new Collection(),
        public readonly array $raw = [],
    ) {
    }
}
