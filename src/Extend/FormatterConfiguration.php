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
use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;

class FormatterConfiguration implements ExtenderInterface
{
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(Container $container, Extension $extension = null)
    {
        $container->make(Dispatcher::class)->listen(
            Configuring::class,
            function (Configuring $event) {
                call_user_func($this->callback, $event->configurator);
            }
        );
    }
}
