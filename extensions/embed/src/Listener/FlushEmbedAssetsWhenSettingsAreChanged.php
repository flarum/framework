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
use Flarum\Embed\EmbedWebApp;
use Flarum\Event\ExtensionWasDisabled;
use Flarum\Event\ExtensionWasEnabled;
use Flarum\Event\SettingWasSet;
use Illuminate\Contracts\Events\Dispatcher;

class FlushEmbedAssetsWhenSettingsAreChanged
{
    /**
     * @var DiscussionController
     */
    protected $webApp;

    /**
     * @param EmbedWebApp $webApp
     */
    public function __construct(EmbedWebApp $webApp)
    {
        $this->webApp = $webApp;
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
            $this->webApp->getAssets()->flushCss();
        }
    }

    public function flushAssets()
    {
        $this->webApp->getAssets()->flush();
    }
}
