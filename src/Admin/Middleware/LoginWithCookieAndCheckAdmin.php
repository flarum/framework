<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin\Middleware;

use Flarum\Api\AccessToken;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;
use Flarum\Forum\Middleware\LoginWithCookie;
use Flarum\Core\Exceptions\PermissionDeniedException;

class LoginWithCookieAndCheckAdmin extends LoginWithCookie
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        if (! $this->logIn($request)) {
            throw new PermissionDeniedException;
        }

        return $out ? $out($request, $response) : $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function getToken(Request $request)
    {
        $token = parent::getToken($request);

        if ($token && $token->user && $token->user->isAdmin()) {
            return $token;
        }
    }
}
