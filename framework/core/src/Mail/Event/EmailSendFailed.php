<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail\Event;

use Flarum\User\User;
use Throwable;

class EmailSendFailed
{
    public function __construct(
        public readonly ?string $recipientEmail,
        public readonly ?string $recipientName,
        public readonly Throwable $exception,
        public readonly ?User $recipient = null,
    ) {
    }
}
