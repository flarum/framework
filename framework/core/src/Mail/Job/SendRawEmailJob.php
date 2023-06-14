<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail\Job;

use Flarum\Queue\AbstractJob;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;

class SendRawEmailJob extends AbstractJob
{
    public function __construct(
        private readonly string $email,
        private readonly string $subject,
        private readonly string $body
    ) {
    }

    public function handle(Mailer $mailer): void
    {
        $mailer->raw($this->body, function (Message $message) {
            $message->to($this->email);
            $message->subject($this->subject);
        });
    }
}
