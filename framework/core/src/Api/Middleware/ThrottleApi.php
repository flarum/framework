<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Flarum\Api\Precognition;
use Flarum\Post\Exception\FloodingException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class ThrottleApi implements Middleware
{
    /**
     * @param array<string, callable> $throttlers
     * @param (callable(Request): bool)|null $precognitionThrottler applies to precognitive requests instead of $throttlers
     */
    public function __construct(
        protected array $throttlers,
        protected $precognitionThrottler = null
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        if ($this->throttle($request)) {
            throw new FloodingException;
        }

        return $handler->handle($request);
    }

    public function throttle(Request $request): bool
    {
        // A precognitive request never saves, so the endpoint's write-oriented
        // throttlers (flood control, email timeouts, ...) neither count it nor
        // deny it. It has its own limit instead, for guests too: validation
        // runs `unique` rules, which could otherwise enumerate taken usernames
        // and emails at an unbounded rate.
        if (Precognition::active($request)) {
            if ($request->getAttribute('bypassThrottling') || $this->precognitionThrottler === null) {
                return false;
            }

            return ($this->precognitionThrottler)($request) === true;
        }

        $throttle = false;
        foreach ($this->throttlers as $throttler) {
            $result = $throttler($request);

            // Explicitly returning false overrides all throttling.
            // Explicitly returning true marks the request to be throttled.
            // Anything else is ignored.
            if ($result === false) {
                return false;
            }

            if ($result === true) {
                $throttle = true;
            }
        }

        return $throttle;
    }
}
