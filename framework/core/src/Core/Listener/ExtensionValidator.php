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

use Flarum\Event\ExtensionWillBeEnabled;
use Flarum\Event\ExtensionWillBeDisabled;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Http\Exception\MethodNotAllowedException;

class ExtensionModificationValidator
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ExtensionWillBeEnabled::class, [$this, 'whenExtensionWillBeEnabled']);
        $events->listen(ExtensionWillBeDisabled::class, [$this, 'whenExtensionWillBeDisabled']);
    }

    /**
     * @param ExtensionWillBeEnabled $event
     */
    public function whenExtensionWillBeEnabled(ExtensionWillBeEnabled $event)
    {
        
    }
    
    /**
     * @param ExtensionWillBeDisabled $event
     * @throws MethodNotAllowedException
     */
    public function whenExtensionWillBeDisabled(ExtensionWillBeDisabled $event)
    {
        if (in_array('flarum-locale', $event->extension->extra)) {
            $default_locale = $this->app->make('flarum.settings')->get('default_locale');
            $locale = array_get($event->extension->extra, 'flarum-locale.code');
            if ($locale === $default_locale) {
                throw new MethodNotAllowedException('You cannot remove all your language packs!');
            }
        }
    }
}
