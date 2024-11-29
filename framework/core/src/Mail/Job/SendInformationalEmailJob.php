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

class SendInformationalEmailJob extends AbstractJob
{
    public function __construct(
        private readonly string $email,
        private readonly string $displayName,
        private readonly string $subject,
        private readonly string $body,
        private readonly string $forumTitle,
        private readonly ?string $bodyTitle = null,
        protected array $views = [
            'text' => 'mail::plain.information.generic',
            'html' => 'mail::html.information.generic'
        ]
    ) {
    }

    public function handle(Mailer $mailer, Factory $view): void
    {
        $forumTitle = $this->forumTitle;
        $infoContent = $this->body;
        $userEmail = $this->email;
        $title = $this->bodyTitle;
        $username = $this->displayName;

        $view->share(compact('forumTitle', 'userEmail', 'title', 'username'));

        $mailer->send(
            $this->views,
            compact('infoContent'),
            function (Message $message) {
                $message->to($this->email);
                $message->subject($this->subject);
            }
        );
    }
}
