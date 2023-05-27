<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;

class NotificationMailer
{
    public function __construct(
        protected Mailer $mailer,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function send(MailableInterface $blueprint, User $user): void
    {
        // Ensure that notifications are delivered to the user in their default language, if they've selected one.
        // If the selected locale is no longer available, the forum default will be used instead.
        $this->translator->setLocale($user->getPreference('locale') ?? $this->settings->get('default_locale'));

        $this->mailer->send(
            $blueprint->getEmailView(),
            compact('blueprint', 'user'),
            function (Message $message) use ($blueprint, $user) {
                $message->to($user->email, $user->display_name)
                        ->subject($blueprint->getEmailSubject($this->translator));
            }
        );
    }
}
