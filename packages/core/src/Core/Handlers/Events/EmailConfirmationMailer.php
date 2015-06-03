<?php namespace Flarum\Core\Handlers\Events;

use Config;
use Flarum\Core\Events\UserWasRegistered;
use Flarum\Core\Events\EmailWasChanged;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailer;

class EmailConfirmationMailer
{
    protected $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('Flarum\Core\Events\UserWasRegistered', __CLASS__.'@whenUserWasRegistered');
        $events->listen('Flarum\Core\Events\EmailWasChanged', __CLASS__.'@whenEmailWasChanged');
    }

    public function whenUserWasRegistered(UserWasRegistered $event)
    {
        $user = $event->user;

        $forumTitle = Config::get('flarum::forum_title');

        $data = [
            'username' => $user->username,
            'forumTitle' => $forumTitle,
            'url' => route('flarum.forum.confirm', ['id' => $user->id, 'token' => $user->confirmation_token])
        ];

        $this->mailer->send(['text' => 'flarum::emails.confirm'], $data, function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Confirm Your Email Address');
        });
    }

    public function whenEmailWasChanged(EmailWasChanged $event)
    {
    }
}
