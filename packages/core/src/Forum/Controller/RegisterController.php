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
    /**
     * @var Client
     */
    protected $api;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @param Client $api
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     */
    public function __construct(Client $api, SessionAuthenticator $authenticator, Rememberer $rememberer)
    {
        $this->api = $api;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request): ResponseInterface
    {
        $params = ['data' => ['attributes' => $request->getParsedBody()]];

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
