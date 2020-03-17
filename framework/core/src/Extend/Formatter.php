<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Formatter\Event\Configuring;
use Flarum\Formatter\Formatter as ActualFormatter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;

class Formatter implements ExtenderInterface, LifecycleInterface
{
    private $callback;

    public function configure($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $events = $container->make(Dispatcher::class);

        $events->listen(
            Configuring::class,
            function (Configuring $event) use ($container) {
                if (is_string($this->callback)) {
                    $callback = $container->make($this->callback);
                } else {
                    $callback = $this->callback;
                }

                $callback($event->configurator);
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
