<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Closure;
use Flarum\Http\EventStream;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sends a few events a moment apart, so the admin dashboard can tell whether
 * this server lets a streamed response reach the browser as it's written, or
 * holds it back until it's complete. Only the browser can tell: the server
 * doesn't know what a proxy in front of it does.
 */
class CheckStreamingController implements RequestHandlerInterface
{
    public const TICKS = 4;

    public const TICK_MS = 250;

    public function __construct(
        protected EventStream $events
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        return $this->events->response(function (Closure $send) {
            $send('start');

            for ($tick = 1; $tick <= self::TICKS; $tick++) {
                usleep(self::TICK_MS * 1000);
                $send('tick');
            }

            $send('done');
        });
    }
}
