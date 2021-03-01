<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Extension\Event\Disabling;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;

class DefaultLanguagePackGuard
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function handle(Disabling $event)
    {
        if (! in_array('flarum-locale', $event->extension->extra)) {
            return;
        }

        $defaultLocale = $this->settings->get('default_locale');
        $locale = Arr::get($event->extension->extra, 'flarum-locale.code');

        if ($locale === $defaultLocale) {
            throw new PermissionDeniedException('You cannot disable the default language pack!');
        }
    }
}
