<?php namespace Flarum\Core\Handlers\Events;

use Flarum\Core\Events\UserWasRegistered;
use Flarum\Core\Events\UserEmailChangeWasRequested;
use Flarum\Core;
use Flarum\Core\Models\EmailToken;
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
        $events->listen('Flarum\Core\Events\UserEmailChangeWasRequested', __CLASS__.'@whenUserEmailChangeWasRequested');
    }

    public function whenUserWasRegistered(UserWasRegistered $event)
    {
        $user = $event->user;
        $data = $this->getPayload($user, $user->email);

        $this->mailer->send(['text' => 'flarum::emails.activateAccount'], $data, function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Activate Your New Account');
        });
    }

    public function whenUserEmailChangeWasRequested(UserEmailChangeWasRequested $event)
    {
        $email = $event->email;
        $data = $this->getPayload($event->user, $email);

        $this->mailer->send(['text' => 'flarum::emails.confirmEmail'], $data, function ($message) use ($email) {
            $message->to($email);
            $message->subject('Confirm Your New Email Address');
        });
    }

    protected function generateToken($user, $email)
    {
        $token = EmailToken::generate($user->id, $email);
        $token->save();

        return $token;
    }

    protected function getPayload($user, $email)
    {
        $token = $this->generateToken($user, $email);

        return [
            'username' => $user->username,
            'url' => route('flarum.forum.confirmEmail', ['token' => $token->id]),
            'forumTitle' => Core::config('forum_title')
        ];
    }
}
