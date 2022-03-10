<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Flarum\Post\Exception\FloodingException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class ThrottleApi implements Middleware
{
    protected $throttlers;

    public function __construct(array $throttlers)
    {
        $this->throttlers = $throttlers;
    }

    public function process(Request $request, Handler $handler): Response
    {
        if ($this->throttle($request)) {
            throw new FloodingException;
        }

        return $handler->handle($request);
    }

    /**
     * @return bool
     */
    public function throttle(Request $request): bool
    {
        $throttle = false;
        foreach ($this->throttlers as $throttler) {
            $result = $throttler($request);

            // Explicitly returning false overrides all throttling.
            // Explicitly returning true marks the request to be throttled.
            // Anything else is ignored.
            if ($result === false) {
                return false;
            } elseif ($result === true) {
                $throttle = true;
            }
        }

        return $throttle;
    }
}
