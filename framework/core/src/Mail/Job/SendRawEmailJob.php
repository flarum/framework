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
    private $email;
    private $subject;
    private $body;

    public function __construct(string $email, string $subject, string $body)
    {
        parent::__construct();

        $this->email = $email;
        $this->subject = $subject;
        $this->body = $body;
    }

    public function handle(Mailer $mailer)
    {
        $mailer->raw($this->body, function (Message $message) {
            $message->to($this->email);
            $message->subject($this->subject);
        });
    }
}
