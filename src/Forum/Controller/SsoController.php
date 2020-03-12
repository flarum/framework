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
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\LoginProvider;
use Flarum\User\RegistrationToken;
use Flarum\User\User;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SsoController implements RequestHandlerInterface
{
    protected $container;

    protected $rememberer;

    protected $settings;
    
    protected $translator;

    public function __construct(
        Container $container,
        Rememberer $rememberer,
        SettingsRepositoryInterface $settings,
        TranslatorInterface $translator
    )
    {
        $this->container = $container;
        $this->rememberer = $rememberer;
        $this->settings = $settings;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request): ResponseInterface
    {
        $actor = $request->getAttribute('actor');

        $driverId = Arr::get($request->getQueryParams(), 'driver');

        $drivers = $this->container->make('flarum.auth.supported_drivers');

        if (! array_key_exists($driverId, $drivers)) {
            throw new RouteNotFoundException;
        }

        if (! $this->settings->get('auth_driver_enabled_'.$driverId, false)) {
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

            // This SSO response is linked to an existing user.
            if ($user = LoginProvider::logIn($driverId, $ssoResponse->getIdentifier())) {
                if ($user->id === $actor->id || $actor->isGuest()) {
                    // The login occured with an existing linked provider for
                    // this user, return logged in response.
                    return $this->makeLoggedInResponse($user);
                } elseif ($user->isAdmin()) {
                    // Looks like an admin was testing this SSO system, return
                    // without logging in.
                    return $this->makeResponse(['testSuccess' => true]);
                } else {
                    // Users without admin rights have no reason to be SSO-ing
                    // into accounts of other users.
                    throw new PermissionDeniedException;
                }
            }

            // SSO response isn't linked to a user, but a user with the provided email exists.
            if (!empty($provided['email']) && $user = User::where(Arr::only($provided, 'email'))->first()) {
                if ($user->id === $actor->id || $actor->isGuest() && $this->settings->get('auth_driver_trust_emails_'.$driverId, false)) {
                    // The current user is linking a new driver to their account.
                    // Or, a new provider is being linked to the account of an existing user, who isnt logged in, because
                    // the sso driver is marked as having trusted emails.
                    return $this->linkProvider($user, $driverId, $ssoResponse->getIdentifier());
                } elseif ($actor->isAdmin()) {
                    // Looks like an admin was testing this SSO system, return
                    // without logging in.
                    return $this->makeResponse(['testSuccess' => true]);
                } else {
                    // Whoever is logging in shouldn't have access to this users account.
                    return new HtmlResponse($this->translator->trans('core.forum.auth.sso.errors.email_already_claimed'));
                }
            }

            if (! $actor->isGuest()) {
                // An already logged in user is linking a new provider, where
                // the email doesn't match the users email. This should still be linked.
                return $this->linkProvider($user, $driverId, $ssoResponse->getIdentifier());
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
        $token = AccessToken::generate($user->id, 3600);
        $token->save();

        event(new LoggedIn($user, $token));
        $response = $this->makeResponse(['loggedIn' => true]);

        return $this->rememberer->rememberUser($response, $user->id);
    }

    private function linkProvider(User $user, $provider, $identifier)
    {
        $user->loginProviders()->create(compact('provider', 'identifier'));

        return $this->makeLoggedInResponse($user);
    }
}
