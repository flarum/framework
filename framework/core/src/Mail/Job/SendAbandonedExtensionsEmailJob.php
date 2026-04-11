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
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Message;

class SendAbandonedExtensionsEmailJob extends AbstractJob
{
    public function __construct(
        private readonly string $email,
        private readonly string $username,
        private readonly string $subject,
        private readonly array $extensionLines,
        private readonly string $forumTitle,
    ) {
    }

    public function handle(Mailer $mailer, Factory $view): void
    {
        $username = $this->username;
        $forumTitle = $this->forumTitle;
        $userEmail = $this->email;
        $extensionLines = $this->extensionLines;

        $view->share(compact('forumTitle', 'userEmail', 'username'));

        $mailer->send(
            [
                'html' => 'mail::html.abandoned_extensions.notify',
                'text' => 'mail::plain.abandoned_extensions.notify',
            ],
            compact('extensionLines'),
            function (Message $message) {
                $message->to($this->email);
                $message->subject($this->subject);
            }
        );
    }
}
