<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Api\Client;
use Flarum\Forum\LogInValidator;
use Flarum\Http\AccessToken;
use Flarum\Http\Controller\AbstractController;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Flarum\User\Event\LoggedIn;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class LogInController extends AbstractController
{
    public function __construct(
        protected UserRepository $users,
        protected Client $apiClient,
        protected SessionAuthenticator $authenticator,
        protected Dispatcher $events,
        protected Rememberer $rememberer,
        protected LogInValidator $validator
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $body = $request->json()->all();
        $params = Arr::only($body, ['identification', 'password', 'remember']);

        $this->validator->assertValid($body);

        $response = $this->apiClient->withParentRequest($request)->withBody($params)->post('/token');

        if ($response->getStatusCode() === 200) {
            $data = $response->getData();

            $token = AccessToken::findValid($data->token);

            $session = $request->attributes->get('session');
            $this->authenticator->logIn($session, $token);

            $this->events->dispatch(new LoggedIn($this->users->findOrFail($data->userId), $token));

            if ($token instanceof RememberAccessToken) {
                $response = $this->rememberer->remember($response, $token);
            }
        }

        return $response;
    }
}
