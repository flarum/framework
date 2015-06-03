<?php namespace Flarum\Core\Notifications;

use Flarum\Core\Models\User;
use Flarum\Core\Models\Forum;
use Illuminate\Contracts\Mail\Mailer;

class NotificationMailer
{
    public function __construct(Mailer $mailer, Forum $forum)
    {
        $this->mailer = $mailer;
        $this->forum = $forum;
    }

    public function send(NotificationInterface $notification, User $user)
    {
        $this->mailer->send(
            $notification->getEmailView(),
            compact('notification', 'user'),
            function ($message) use ($notification, $user) {
                $message->to($user->email, $user->username)
                        ->subject('['.$this->forum->title.'] '.$notification->getEmailSubject());
            }
        );
    }
}
