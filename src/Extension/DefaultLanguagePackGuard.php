<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Extension\Event\Disabling;
use Flarum\Http\Exception\ForbiddenException;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;

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

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Disabling::class, [$this, 'whenExtensionWillBeDisabled']);
    }

    /**
     * @param Disabling $event
     * @throws ForbiddenException
     */
    public function whenExtensionWillBeDisabled(Disabling $event)
    {
        if (in_array('flarum-locale', $event->extension->extra)) {
            $defaultLocale = $this->settings->get('default_locale');
            $locale = array_get($event->extension->extra, 'flarum-locale.code');
            if ($locale === $defaultLocale) {
                throw new ForbiddenException('You cannot disable the default language pack!');
            }
        }
    }
}
