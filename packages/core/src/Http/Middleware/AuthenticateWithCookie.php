<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Http\Exception\TokenMismatchException;
use Flarum\Http\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class AuthenticateWithCookie implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $id = array_get($request->getCookieParams(), 'flarum_session');

        if ($id) {
            $session = Session::find($id);

            $request = $request->withAttribute('session', $session);

            if (! $this->isReading($request) && ! $this->tokensMatch($request)) {
                throw new TokenMismatchException;
            }
        }

        return $out ? $out($request, $response) : $response;
    }

    private function isReading(Request $request)
    {
        return in_array($request->getMethod(), ['HEAD', 'GET', 'OPTIONS']);
    }

    private function tokensMatch(Request $request)
    {
        $input = $request->getHeaderLine('X-CSRF-Token') ?: array_get($request->getParsedBody(), 'token');

        return $request->getAttribute('session')->csrf_token === $input;
    }
}
