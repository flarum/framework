<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Embed\Listener;

use Flarum\Embed\DiscussionController;
use Flarum\Event\ExtensionWasDisabled;
use Flarum\Event\ExtensionWasEnabled;
use Flarum\Event\SettingWasSet;
use Illuminate\Contracts\Events\Dispatcher;

class FlushEmbedAssetsWhenSettingsAreChanged
{
    /**
     * @var DiscussionController
     */
    protected $controller;

    /**
     * @param DiscussionController $controller
     */
    public function __construct(DiscussionController $controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(SettingWasSet::class, [$this, 'flushCss']);
        $events->listen(ExtensionWasEnabled::class, [$this, 'flushAssets']);
        $events->listen(ExtensionWasDisabled::class, [$this, 'flushAssets']);
    }

    /**
     * @param SettingWasSet $event
     */
    public function flushCss(SettingWasSet $event)
    {
        if (preg_match('/^theme_|^custom_less$/i', $event->key)) {
            $this->controller->flushCss();
        }
    }

    public function flushAssets()
    {
        $this->controller->flushAssets();
    }
}
