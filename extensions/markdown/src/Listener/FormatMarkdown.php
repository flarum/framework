<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Markdown\Listener;

use Flarum\Formatter\Event\Configuring;
use Illuminate\Contracts\Events\Dispatcher;

class FormatMarkdown
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Configuring::class, [$this, 'addMarkdownFormatter']);
    }

    /**
     * @param Configuring $event
     */
    public function addMarkdownFormatter(Configuring $event)
    {
        $event->configurator->Litedown;
    }
}
