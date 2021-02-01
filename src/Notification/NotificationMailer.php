<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationMailer
{
    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param Mailer $mailer
     * @param TranslatorInterface $translator
     */
    public function __construct(Mailer $mailer, TranslatorInterface $translator)
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
    }

    /**
     * @param MailableInterface $blueprint
     * @param User $user
     */
    public function send(MailableInterface $blueprint, User $user)
    {
        $this->mailer->send(
            $blueprint->getEmailView(),
            compact('blueprint', 'user'),
            function (Message $message) use ($blueprint, $user) {
                $message->to($user->email, $user->username)
                        ->subject($blueprint->getEmailSubject($this->translator));
            }
        );
    }
}
