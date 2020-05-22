<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Http\UrlGenerator;
use Flarum\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param Mailer $mailer
     * @param TranslatorInterface $translator
     * @param UrlGenerator $url
     */
    public function __construct(Mailer $mailer, TranslatorInterface $translator, UrlGenerator $url)
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->url = $url;
    }

    /**
     * @param MailableInterface $blueprint
     * @param User $user
     */
    public function send(MailableInterface $blueprint, User $user)
    {
        $translator = $this->translator;
        $url = $this->url;
        $this->mailer->send(
            $blueprint->getEmailView(),
            compact('blueprint', 'user', 'translator', 'url'),
            function (Message $message) use ($blueprint, $user) {
                $message->to($user->email, $user->username)
                        ->subject($blueprint->getEmailSubject());
            }
        );
    }
}
