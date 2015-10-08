<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Listener;

use Flarum\Core;
use Flarum\Core\EmailToken;
use Flarum\Core\User;
use Flarum\Event\UserEmailChangeWasRequested;
use Flarum\Event\UserWasRegistered;
use Flarum\Forum\UrlGenerator;
use Flarum\Settings\SettingsRepository;
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
     * @var UrlGenerator
     */
    private $url;

    /**
     * @param \Flarum\Settings\SettingsRepository $settings
     * @param Mailer $mailer
     * @param UrlGenerator $url
     */
    public function __construct(SettingsRepository $settings, Mailer $mailer, UrlGenerator $url)
    {
        $this->settings = $settings;
        $this->mailer = $mailer;
        $this->url = $url;
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
     * @param \Flarum\Event\UserWasRegistered $event
     */
    public function whenUserWasRegistered(UserWasRegistered $event)
    {
        $user = $event->user;

        if ($user->is_activated) {
            return;
        }

        $data = $this->getEmailData($user, $user->email);

        $this->mailer->send(['text' => 'flarum::emails.activateAccount'], $data, function (Message $message) use ($user) {
            $message->to($user->email);
            $message->subject('Activate Your New Account');
        });
    }

    /**
     * @param \Flarum\Event\UserEmailChangeWasRequested $event
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
     *
     * @return EmailToken
     */
    protected function generateToken(User $user, $email)
    {
        $token = EmailToken::generate($email, $user->id);
        $token->save();

        return $token;
    }

    /**
     * Get the data that should be made available to email templates.
     *
     * @param User $user
     * @param string $email
     *
     * @return array
     */
    protected function getEmailData(User $user, $email)
    {
        $token = $this->generateToken($user, $email);

        // TODO: Need to use AbstractUrlGenerator, but since this is part of core we
        // don't know that the forum routes will be loaded. Should the confirm
        // email route be part of core??
        return [
            'username' => $user->username,
            'url' => $this->url->toRoute('confirmEmail', ['token' => $token->id]),
            'forumTitle' => $this->settings->get('forum_title')
        ];
    }
}
