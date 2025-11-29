<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Concerns;

use React\Promise\PromiseInterface;

trait Promises
{
    protected function createFulfilledPromise(mixed $value): PromiseInterface
    {
        return \React\Promise\resolve($value);
    }
}
