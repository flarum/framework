<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Auth;

use Flarum\Http\Rememberer;
use Flarum\User\LoginProvider;
use Flarum\User\RegistrationToken;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\HtmlResponse;

class ResponseFactory
{
    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @param Rememberer $rememberer
     */
    public function __construct(Rememberer $rememberer)
    {
        $this->rememberer = $rememberer;
    }

    public function make(string $provider, string $identifier, callable $configureRegistration): ResponseInterface
    {
        if ($user = LoginProvider::logIn($provider, $identifier)) {
            return $this->makeLoggedInResponse($user);
        }

        $configureRegistration($registration = new Registration);

        $provided = $registration->getProvided();

        if (! empty($provided['email']) && $user = User::where(array_only($provided, 'email'))->first()) {
            $user->loginProviders()->create(compact('provider', 'identifier'));

            return $this->makeLoggedInResponse($user);
        }

        $token = RegistrationToken::generate($provider, $identifier, $provided, $registration->getPayload());
        $token->save();

        return $this->makeResponse(array_merge(
            $provided,
            $registration->getSuggested(),
            [
                'token' => $token->id,
                'provided' => array_keys($provided)
            ]
        ));
    }

    private function makeResponse(array $payload): HtmlResponse
    {
        $content = sprintf(
            '<script>window.close(); window.opener.app.authenticationComplete(%s);</script>',
            json_encode($payload)
        );

        return new HtmlResponse($content);
    }

    private function makeLoggedInResponse(User $user)
    {
        $response = $this->makeResponse(['loggedIn' => true]);

        return $this->rememberer->rememberUser($response, $user->id);
    }
}
