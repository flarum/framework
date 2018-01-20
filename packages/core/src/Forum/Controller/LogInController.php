<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Api\Client;
use Flarum\Api\Controller\TokenController;
use Flarum\Http\AccessToken;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Flarum\User\Event\LoggedIn;
use Flarum\User\UserRepository;
use Psr\Http\Message\ServerRequestInterface as Request;

class LogInController implements ControllerInterface
{
    /**
     * @var \Flarum\User\UserRepository
     */
    protected $users;

    /**
     * @var Client
     */
    protected $apiClient;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @param \Flarum\User\UserRepository $users
     * @param Client $apiClient
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     */
    public function __construct(UserRepository $users, Client $apiClient, SessionAuthenticator $authenticator, Rememberer $rememberer)
    {
        $this->users = $users;
        $this->apiClient = $apiClient;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request)
    {
        $actor = $request->getAttribute('actor');
        $body = $request->getParsedBody();
        $params = array_only($body, ['identification', 'password']);

        $response = $this->apiClient->send(TokenController::class, $actor, [], $params);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody());

            $session = $request->getAttribute('session');
            $this->authenticator->logIn($session, $data->userId);

            $token = AccessToken::find($data->token);

            event(new LoggedIn($this->users->findOrFail($data->userId), $token));

            if (array_get($body, 'remember')) {
                $response = $this->rememberer->remember($response, $token);
            }
        }

        return $response;
    }
}
