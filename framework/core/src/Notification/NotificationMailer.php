<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Support\Str;

class NotificationMailer
{
    public function __construct(
        protected Mailer $mailer,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url
    ) {
    }

    public function send(MailableInterface $blueprint, User $user): void
    {
        // Ensure that notifications are delivered to the user in their default language, if they've selected one.
        $this->translator->setLocale($user->getPreference('locale') ?? $this->settings->get('default_locale'));

        // Generate and save the unsubscribe token:
        $token = Str::random(60);
        $unsubscribeRecord = new UnsubscribeToken([
            'user_id'    => $user->id,
            'email_type' => $blueprint::getType(),
            'token'      => $token
        ]);
        $unsubscribeRecord->save();

        $unsubscribeLink = $this->url->to('forum')->route('notifications.unsubscribe', ['userId' => $user->id, 'token' => $token]);
        $settingsLink = $this->url->to('forum')->route('settings');

        $this->mailer->send(
            $blueprint->getEmailViews(),
            compact('blueprint', 'user', 'unsubscribeLink', 'settingsLink'),
            function (Message $message) use ($blueprint, $user, $unsubscribeLink) {
                $message->to($user->email, $user->display_name)
                        ->subject($blueprint->getEmailSubject($this->translator))
                        ->getHeaders()
                        ->addTextHeader('List-Unsubscribe', '<'.$unsubscribeLink.'>');
            }
        );
    }
}
