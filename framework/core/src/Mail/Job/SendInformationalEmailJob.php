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

class SendInformationalEmailJob extends AbstractJob
{
    public function __construct(
        private readonly string $email,
        private readonly string $subject,
        private readonly string $body,
        private readonly string $forumTitle,
        private readonly ?string $bodyTitle = null,
        protected array $views = [
            'plain' => 'flarum.forum::email.information.plain.base',
            'html' => 'flarum.forum::email.information.html.base'
        ]
    ) {
    }

    public function handle(Mailer $mailer): void
    {
        $forumTitle = $this->forumTitle;
        $infoContent = $this->body;
        $userEmail = $this->email;
        $title = $this->bodyTitle;

        $mailer->send(
            $this->views,
            compact('forumTitle', 'infoContent', 'userEmail', 'title'),
            function (Message $message) {
                $message->to($this->email);
                $message->subject($this->subject);
            }
        );
    }
}
