<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Emoji\Listener;

use Flarum\Formatter\Event\Configuring;
use Illuminate\Contracts\Events\Dispatcher;

class FormatEmoticons
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Configuring::class, [$this, 'addEmoticons']);
    }

    /**
     * @param Configuring $event
     */
    public function addEmoticons(Configuring $event)
    {
        $event->configurator->Emoji->useEmojiOne();
        $event->configurator->Emoji->omitImageSize();
        $event->configurator->Emoji->useSVG();

        $event->configurator->Emoji->addAlias(':)', '🙂');
        $event->configurator->Emoji->addAlias(':D', '😃');
        $event->configurator->Emoji->addAlias(':P', '😛');
        $event->configurator->Emoji->addAlias(':(', '🙁');
        $event->configurator->Emoji->addAlias(':|', '😐');
        $event->configurator->Emoji->addAlias(';)', '😉');
        $event->configurator->Emoji->addAlias(':\'(', '😢');
        $event->configurator->Emoji->addAlias(':O', '😮');
        $event->configurator->Emoji->addAlias('>:(', '😡');
    }
}
