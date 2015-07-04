<?php namespace Flarum\Core\Notifications;

use Flarum\Core\Users\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;

class NotificationMailer
{
    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param MailableBlueprint $blueprint
     * @param User $user
     */
    public function send(MailableBlueprint $blueprint, User $user)
    {
        $this->mailer->send(
            $blueprint->getEmailView(),
            compact('notification', 'user'),
            function (Message $message) use ($blueprint, $user) {
                $message->to($user->email, $user->username)
                        ->subject($blueprint->getEmailSubject());
            }
        );
    }
}
