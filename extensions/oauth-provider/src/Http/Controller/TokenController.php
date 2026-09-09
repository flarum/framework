<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Http\Controller;

use Flarum\OAuthProvider\Server\AuthorizationServerFactory;
use Laminas\Diactoros\Response;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class TokenController implements RequestHandlerInterface
{
    public function __construct(protected AuthorizationServerFactory $factory)
    {
    }

    public function handle(Request $request): ResponseInterface
    {
        $server = $this->factory->authorizationServer();

        try {
            return $server->respondToAccessTokenRequest($request, new Response());
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse(new Response());
        }
    }
}
