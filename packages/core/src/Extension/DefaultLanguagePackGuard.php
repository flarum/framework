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
use Illuminate\Contracts\Events\Dispatcher;

class DefaultLanguagePackGuard
{
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
            $default_locale = $this->app->make('flarum.settings')->get('default_locale');
            $locale = array_get($event->extension->extra, 'flarum-locale.code');
            if ($locale === $default_locale) {
                throw new ForbiddenException('You cannot disable the default language pack!');
            }
        }
    }
}
