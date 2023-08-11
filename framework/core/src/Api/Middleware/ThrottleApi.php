<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Closure;
use Flarum\Http\Middleware\IlluminateMiddlewareInterface;
use Flarum\Post\Exception\FloodingException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleApi implements IlluminateMiddlewareInterface
{
    public function __construct(
        protected array $throttlers
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->throttle($request)) {
            throw new FloodingException;
        }

        return $next($request);
    }

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
