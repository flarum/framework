<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event\Registered;
use Illuminate\Contracts\Queue\Queue;

class AccountActivationMailer
{
    use AccountActivationMailerTrait;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Queue $queue,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator
    ) {
    }

    public function handle(Registered $event): void
    {
        $user = $event->user;

        if ($user->is_email_confirmed) {
            return;
        }

        $token = $this->generateToken($user, $user->email);
        $data = $this->getEmailData($user, $token);

        $this->sendConfirmationEmail($user, $data);
    }
}
