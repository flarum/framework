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

use Flarum\Event\ExtensionWillBeDisabled;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Events\Dispatcher;

class ExtensionChangeValidator
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ExtensionWillBeDisabled::class, [$this, 'whenExtensionWillBeDisabled']);
    }

    /**
     * @param ExtensionWillBeDisabled $event
     * @throws MethodNotAllowedException
     */
    public function whenExtensionWillBeDisabled(ExtensionWillBeDisabled $event)
    {
        list($status, $reason) = $this->canRemoveExtension($event->extension);
        if (! $status) {
            throw new MethodNotAllowedException($reason);
        }
    }

    /**
     * @param Extension $extension
     * @return [boolean, string|null]
     */
    protected function canRemoveExtension(Extension $extension)
    {
        if (in_array('locale', $extension->keyworkds) || in_array('flarum-locale', $extension->extra)) {
            $default_locale = $this->app->make('flarum.settings')->get('default_locale');
            $locale = array_get($extension->extra, 'flarum-locale.code');
            if ($locale === $default_locale) {
                return [false, 'Cannot remove your current language pack'];
            }
        }

        return [true, null];
    }
}
