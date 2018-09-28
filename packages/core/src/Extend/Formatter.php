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

use Flarum\Extension\Extension;
use Flarum\Formatter\Event\Configuring;
use Flarum\Formatter\Formatter as ActualFormatter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;

class Formatter implements ExtenderInterface, LifecycleInterface
{
    protected $callback;

    public function configure(callable $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $events = $container->make(Dispatcher::class);

        $events->listen(
            Configuring::class,
            function (Configuring $event) {
                call_user_func($this->callback, $event->configurator);
            }
        );
    }

    public function onEnable(Container $container, Extension $extension)
    {
        // FLush the formatter cache when this extension is enabled
        $container->make(ActualFormatter::class)->flush();
    }

    public function onDisable(Container $container, Extension $extension)
    {
        // FLush the formatter cache when this extension is disabled
        $container->make(ActualFormatter::class)->flush();
    }
}
