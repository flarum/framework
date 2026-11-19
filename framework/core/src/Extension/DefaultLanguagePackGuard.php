<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Extension\Event\Disabling;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;

class DefaultLanguagePackGuard
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected ?LocaleManager $locales = null
    ) {
    }

    public function handle(Disabling $event): void
    {
        $defaultLocale = $this->settings->get('default_locale');

        $declaredInComposerJson = Arr::has($event->extension->extra, 'flarum-locale')
            && Arr::get($event->extension->extra, 'flarum-locale.code') === $defaultLocale;
        $registeredByExtender = $this->locales?->getLanguagePackId($defaultLocale) === $event->extension->getId();

        if ($declaredInComposerJson || $registeredByExtender) {
            throw new PermissionDeniedException('You cannot disable the default language pack!');
        }
    }
}
