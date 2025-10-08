<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Locale\Translator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\UserRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

trait SetTranslatorLocaleForEmailTrait
{
    /**
     * Set the translator locale based on the user's preference for the given email.
     * Falls back to forum default if user not found or has no preference.
     */
    protected function setTranslatorLocaleForEmail(
        TranslatorInterface $translator,
        SettingsRepositoryInterface $settings,
        string $email
    ): void {
        $users = resolve(UserRepository::class);
        $user = $users->findByEmail($email);
        
        $locale = $user 
            ? ($user->getPreference('locale') ?? $settings->get('default_locale'))
            : $settings->get('default_locale');

        /** @var TranslatorInterface&Translator $translator */
        $translator->setLocale($locale);
    }
}
