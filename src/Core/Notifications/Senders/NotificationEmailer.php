<?php namespace Flarum\Core\Notifications\Senders;

use Flarum\Core\Notifications\Types\Notification;
use Flarum\Core\Models\User;
use Flarum\Core\Models\Forum;
use Illuminate\Mail\Mailer;
use ReflectionClass;

class NotificationEmailer implements NotificationSender
{
    public function __construct(Mailer $mailer, Forum $forum)
    {
        $this->mailer = $mailer;
        $this->forum = $forum;
    }

    public function send(Notification $notification, User $user)
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

    public static function compatibleWith($class)
    {
        return (new ReflectionClass($class))->implementsInterface('Flarum\Core\Notifications\Types\EmailableNotification');
    }
}
