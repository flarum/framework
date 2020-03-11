<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Forum\Auth\SsoResponse;
use Flarum\Http\AccessToken;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\Rememberer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event\LoggedIn;
use Flarum\User\LoginProvider;
use Flarum\User\RegistrationToken;
use Flarum\User\User;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class SsoController implements RequestHandlerInterface
{
    protected $container;

    protected $rememberer;

    protected $settings;

    public function __construct(
        Container $container,
        Rememberer $rememberer,
        SettingsRepositoryInterface $settings
    )
    {
        $this->container = $container;
        $this->rememberer = $rememberer;
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request): ResponseInterface
    {
        $driverId = Arr::get($request->getQueryParams(), 'driver');

        $drivers = $this->container->make('forum.auth.supported_drivers');

        if (! array_key_exists($driverId, $drivers)) {
            throw new RouteNotFoundException;
        }

        if (! in_array($driverId, json_decode($this->settings->get('enabled_auth_drivers'), true))) {
            throw new RouteNotFoundException;
        }

        $driver = $this->container->make($drivers[$driverId]);

        $ssoResponse = $driver->sso($request, new SsoResponse($driverId));

        if ($ssoResponse instanceof ResponseInterface) {
            // This could be anything from an error page to a redirect
            // to the external sso provider. We return this directly.
            return $ssoResponse;
        } elseif ($ssoResponse instanceof SsoResponse) {
            // This means the authentication was successful.
            $provided = $ssoResponse->getProvided();

            if ($user = LoginProvider::logIn($driverId, $ssoResponse->getIdentifier())) {
                return $this->makeLoggedInResponse($user);
            }

            if (!empty($provided['email']) && $user = User::where(Arr::only($provided, 'email'))->first()) {
                if ($driver->trustEmails()) {
                    $user->loginProviders()->create(compact('provider', 'identifier'));

                    return $this->makeLoggedInResponse($user);
                } elseif ($request->getAttribute('actor')->isGuest()) {
                    // TODO: Add Translation.
                    return new HtmlResponse("A User with this email already exists. Please add this driver through your settings panel.");
                }
            }

            // No existing user found, creating new user.

            $token = RegistrationToken::generate($driverId, $ssoResponse->getIdentifier(), $provided, $ssoResponse->getPayload());
            $token->save();

            return $this->makeResponse(array_merge(
                $provided,
                $ssoResponse->getSuggested(),
                [
                    'token' => $token->token,
                    'provided' => array_keys($provided)
                ]
            ));
        }
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
        $token = AccessToken::generate($user->id, $lifetime);
        $token->save();

        event(new LoggedIn($user, $token));
        $response = $this->makeResponse(['loggedIn' => true]);

        return $this->rememberer->rememberUser($response, $user->id);
    }
}
