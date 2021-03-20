<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Foundation\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface;

class ReferrerPolicyHeader implements Middleware
{
    protected $policy = '';

    public function __construct(Config $config)
    {
        $this->policy = $config['headers']['referrer-policy'] ?? 'same-origin';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $response = $response->withAddedHeader('Referrer-Policy', $this->policy);

        return $response;
    }
}
