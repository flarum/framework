<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Jobs;

use Flarum\Core\Queue\AbstractJob;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;

class MailJob extends AbstractJob
{
    /**
     * Body of the mail message.
     *
     * @var string
     */
    protected $body;

    /**
     * Receiver email address.
     *
     * @var string
     */
    protected $to;

    /**
     * Subject of the mail message.
     *
     * @var string
     */
    protected $subject;

    public function __construct($subject, $body, $to)
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->to = $to;
    }

    public function handle(Mailer $mailer)
    {
        $mailer->raw($this->body, function(Message $message) {
            $message
                ->to($this->to)
                ->subject($this->subject);
        });
    }
}
