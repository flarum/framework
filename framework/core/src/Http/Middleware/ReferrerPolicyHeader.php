<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Closure;
use Flarum\Foundation\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class ReferrerPolicyHeader implements IlluminateMiddlewareInterface
{
    protected string $policy = '';

    public function __construct(Config $config)
    {
        $this->policy = strval(Arr::get($config, 'headers.referrerPolicy') ?? 'same-origin');
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Referrer-Policy', $this->policy);

        return $response;
    }
}
