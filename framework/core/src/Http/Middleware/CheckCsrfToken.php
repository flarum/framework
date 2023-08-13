<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Closure;
use Flarum\Http\Exception\TokenMismatchException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCsrfToken implements IlluminateMiddlewareInterface
{
    public function __construct(
        protected array $exemptRoutes
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(...$this->exemptRoutes)) {
            return $next($request);
        }

        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        if ($request->attributes->get('bypassCsrfToken', false)) {
            return $next($request);
        }

        if ($this->tokensMatch($request)) {
            return $next($request);
        }

        throw new TokenMismatchException('CSRF token did not match');
    }

    private function tokensMatch(Request $request): bool
    {
        $expected = (string) $request->attributes->get('session')->token();

        $provided = $request->json('csrfToken', $request->header('X-CSRF-Token'));

        if (! is_string($provided)) {
            return false;
        }

        return hash_equals($expected, $provided);
    }
}
