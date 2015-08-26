<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Users\Listeners;

use Flarum\Core\Settings\SettingsRepository;
use Flarum\Events\UserWasRegistered;
use Flarum\Events\UserEmailChangeWasRequested;
use Flarum\Core;
use Flarum\Core\Users\EmailToken;
use Flarum\Core\Users\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;

class EmailConfirmationMailer
{
    /**
     * @var SettingsRepository
     */
    protected $settings;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @param SettingsRepository $settings
     * @param Mailer $mailer
     */
    public function __construct(SettingsRepository $settings, Mailer $mailer)
    {
        $this->settings = $settings;
        $this->mailer = $mailer;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(UserWasRegistered::class, [$this, 'whenUserWasRegistered']);
        $events->listen(UserEmailChangeWasRequested::class, [$this, 'whenUserEmailChangeWasRequested']);
    }

    /**
     * @param \Flarum\Events\UserWasRegistered $event
     */
    public function whenUserWasRegistered(UserWasRegistered $event)
    {
        $user = $event->user;
        $data = $this->getEmailData($user, $user->email);

        $this->mailer->send(['text' => 'flarum::emails.activateAccount'], $data, function (Message $message) use ($user) {
            $message->to($user->email);
            $message->subject('Activate Your New Account');
        });
    }

    /**
     * @param \Flarum\Events\UserEmailChangeWasRequested $event
     */
    public function whenUserEmailChangeWasRequested(UserEmailChangeWasRequested $event)
    {
        $email = $event->email;
        $data = $this->getEmailData($event->user, $email);

        $this->mailer->send(['text' => 'flarum::emails.confirmEmail'], $data, function (Message $message) use ($email) {
            $message->to($email);
            $message->subject('Confirm Your New Email Address');
        });
    }

    /**
     * @param User $user
     * @param string $email
     * @return EmailToken
     */
    protected function generateToken(User $user, $email)
    {
        $token = EmailToken::generate($user->id, $email);
        $token->save();

        return $token;
    }

    /**
     * Get the data that should be made available to email templates.
     *
     * @param User $user
     * @param string $email
     * @return array
     */
    protected function getEmailData(User $user, $email)
    {
        $token = $this->generateToken($user, $email);

        // TODO: Need to use UrlGenerator, but since this is part of core we
        // don't know that the forum routes will be loaded. Should the confirm
        // email route be part of core??
        return [
            'username' => $user->username,
            'url' => Core::url().'/confirm/'.$token->id,
            'forumTitle' => $this->settings->get('forum_title')
        ];
    }
}
