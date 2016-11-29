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


use Illuminate\Contracts\Events\Dispatcher;
use ReflectionFunction;

class Listener
{
    protected $event;

    protected $listener;

    protected $priority;

    public function __construct(callable $listener, $priority = null)
    {
        $this->listener = $listener;
        $this->priority = $priority;

        $reflection = new ReflectionFunction($listener);
        $parameters = $reflection->getParameters();
        $this->event = $parameters[0]->getType();
    }

    public function __invoke(Dispatcher $events)
    {
        $events->listen($this->event, $this->listener);
    }
}
