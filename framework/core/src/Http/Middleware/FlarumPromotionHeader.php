<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Foundation\Config;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface;

class FlarumPromotionHeader implements Middleware
{
    protected bool $enabled = true;

    public function __construct(Config $config)
    {
        $this->enabled = (bool) (Arr::get($config, 'headers.poweredByHeader') ?? true);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($this->enabled) {
            $response = $response->withAddedHeader('X-Powered-By', 'Flarum');
        }

        return $response;
    }
}
