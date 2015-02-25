<?php namespace Flarum\Core\Handlers\Events;

use Illuminate\Mail\Mailer;
use Flarum\Core\Events\UserWasRegistered;
use Flarum\Core\Events\EmailWasChanged;
use Config;

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
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
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
            'url' => route('flarum.confirm', ['id' => $user->id, 'token' => $user->confirmation_token])
        ];

        $this->mailer->send(['text' => 'flarum::emails.confirm'], $data, function ($message) use ($user, $forumTitle) {
            $message->to($user->email);
            $message->subject('['.$forumTitle.'] Email Address Confirmation');
            $message->from('noreply@localhost', $forumTitle);
        });
    }

    public function whenEmailWasChanged(EmailWasChanged $event)
    {

    }
}
