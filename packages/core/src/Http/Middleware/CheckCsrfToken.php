<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Http\Exception\TokenMismatchException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class CheckCsrfToken implements Middleware
{
    public function process(Request $request, Handler $handler): Response
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $handler->handle($request);
        }

        if ($request->getAttribute('bypassCsrfToken', false)) {
            return $handler->handle($request);
        }

        if ($this->tokensMatch($request)) {
            return $handler->handle($request);
        }

        throw new TokenMismatchException('CSRF token did not match');
    }

    private function tokensMatch(Request $request): bool
    {
        $expected = (string) $request->getAttribute('session')->token();

        $provided = $request->getParsedBody()['csrfToken'] ??
            $request->getHeaderLine('X-CSRF-Token');

        return hash_equals($expected, $provided);
    }
}
