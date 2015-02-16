<?php namespace Flarum\Core\Listeners;

use Illuminate\Mail\Mailer;
use Laracasts\Commander\Events\EventListener;

use Flarum\Core\Users\Events\UserWasRegistered;
use Flarum\Core\Users\Events\EmailWasChanged;

class EmailConfirmationMailer extends EventListener
{
    protected $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function whenUserWasRegistered(UserWasRegistered $event)
    {
        $user = $event->user;

        $data = [
            'user' => $user,
            'url' => route('flarum.confirm', ['id' => $user->id, 'token' => $user->confirmation_token])
        ];

        $this->mailer->send('flarum::emails.confirm', $data, function ($message) use ($user) {
            $message->to($user->email)->subject('Welcome!');
        });
    }

    public function whenEmailWasChanged(EmailWasChanged $event)
    {

    }
}
