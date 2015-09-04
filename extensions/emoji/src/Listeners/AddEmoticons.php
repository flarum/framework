<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Emoji\Listeners;

use Flarum\Events\FormatterConfigurator;
use Illuminate\Contracts\Events\Dispatcher;

class AddEmoticons
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(FormatterConfigurator::class, [$this, 'addEmoticons']);
    }

    public function addEmoticons(FormatterConfigurator $event)
    {
        $event->configurator->Emoticons->add(':)', '&#x1f604;');
        $event->configurator->Emoticons->add(':D', '&#x1f603;');
        $event->configurator->Emoticons->add(':P', '&#x1f61c;');
        $event->configurator->Emoticons->add(':(', '&#x1f61f;');
        $event->configurator->Emoticons->add(':|', '&#x1f610;');
        $event->configurator->Emoticons->add(';)', '&#x1f609;');
        $event->configurator->Emoticons->add(':*', '&#x1f618;');
        $event->configurator->Emoticons->add(':\'(', '&#x1f622;');
        $event->configurator->Emoticons->add(':\')', '&#x1f602;');
        $event->configurator->Emoticons->add(':O', '&#x1f62e;');
        $event->configurator->Emoticons->add('B)', '&#x1f60e;');
        $event->configurator->Emoticons->add('>:(', '&#x1f621;');
    }
}
