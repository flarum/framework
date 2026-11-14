<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail\Event;

use Flarum\User\User;

/**
 * Fired when a mail driver reports that a recipient marked a message as spam
 * (a complaint). Treated at least as seriously as a hard bounce.
 */
class EmailComplained
{
    public function __construct(
        public readonly string $email,
        public readonly string $reason,
        public readonly ?User $recipient = null,
        public readonly array $raw = [],
    ) {
    }
}
