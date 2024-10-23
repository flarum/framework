<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Api\Client;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class RegisterController implements RequestHandlerInterface
{
    public function __construct(
        protected Client $api,
        protected SessionAuthenticator $authenticator,
        protected Rememberer $rememberer
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $params = ['data' => ['type' => 'users', 'attributes' => $request->getParsedBody() ?? []]];

        $response = $this->api->withParentRequest($request)->withBody($params)->post('/users');

        $body = json_decode($response->getBody());

        if (isset($body->data)) {
            $userId = $body->data->id;

            $token = RememberAccessToken::generate($userId);

            $session = $request->getAttribute('session');
            $this->authenticator->logIn($session, $token);

            $response = $this->rememberer->remember($response, $token);
        }

        return $response;
    }
}
