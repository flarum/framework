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

class FlarumPromotionHeader implements IlluminateMiddlewareInterface
{
    protected bool $enabled = true;

    public function __construct(Config $config)
    {
        $this->enabled = (bool) (Arr::get($config, 'headers.poweredByHeader') ?? true);
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->enabled) {
            $response->headers->set('X-Powered-By', 'Flarum');
        }

        return $response;
    }
}
