<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Extension\Extension;
use Flarum\Formatter\Event\Configuring;
use Flarum\Formatter\Formatter as ActualFormatter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;

class Formatter implements ExtenderInterface
{
    protected $callback;

    public function configure(callable $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    public function __invoke(Container $container, Extension $extension = null)
    {
        $events = $container->make(Dispatcher::class);

        $events->listen(
            Configuring::class,
            function (Configuring $event) {
                call_user_func($this->callback, $event->configurator);
            }
        );

        // Also set up an event listener to flush the formatter cache whenever
        // this extension is enabled or disabled.
        $flush = function ($event) use ($container, $extension) {
            if ($event->extension === $extension) {
                $container->make(ActualFormatter::class)->flush();
            }
        };

        $events->listen(Enabled::class, $flush);
        $events->listen(Disabled::class, $flush);
    }
}
