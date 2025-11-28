<?php

namespace Flarum\Realtime\Websocket\Concerns;

use React\Promise\PromiseInterface;

trait Promises
{
    protected function createFulfilledPromise($value): PromiseInterface
    {
        return \React\Promise\resolve($value);
    }
}
