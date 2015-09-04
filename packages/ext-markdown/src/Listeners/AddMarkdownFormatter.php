<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Markdown\Listeners;

use Flarum\Events\FormatterConfigurator;
use Illuminate\Contracts\Events\Dispatcher;

class AddMarkdownFormatter
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(FormatterConfigurator::class, [$this, 'addMarkdownFormatter']);
    }

    public function addMarkdownFormatter(FormatterConfigurator $event)
    {
        $event->configurator->Litedown;
    }
}
