<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Auth;

use Flarum\Http\RememberAccessToken;
use Flarum\Http\Rememberer;
use Flarum\User\LoginProvider;
use Flarum\User\RegistrationToken;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory
{
    public function __construct(
        protected Rememberer $rememberer
    ) {
    }

    /**
     * Handle an OAuth callback by logging in an existing user or beginning
     * the registration flow for a new one.
     *
     * @param string   $provider              The OAuth provider name (e.g. 'github').
     * @param string   $identifier            The provider's unique identifier for this user.
     * @param callable $configureRegistration Callback that populates a {@see Registration} instance
     *                                        with data from the provider (email, username, avatar, etc.).
     * @param string   $returnTo              Validated same-origin URL to redirect to after login.
     *                                        Must be validated by the caller before being passed here.
     */
    public function make(
        string $provider,
        string $identifier,
        callable $configureRegistration,
        string $returnTo = '/'
    ): ResponseInterface {
        if ($user = LoginProvider::logIn($provider, $identifier)) {
            return $this->makeLoggedInResponse($user, $returnTo);
        }

        $configureRegistration($registration = new Registration);

        $provided = $registration->getProvided();

        if (! empty($provided['email']) && $user = User::where(Arr::only($provided, 'email'))->first()) {
            $user->loginProviders()->create(compact('provider', 'identifier'));

            // Append _flarum_linked so the frontend can show a confirmation modal.
            $separator = str_contains($returnTo, '?') ? '&' : '?';
            $returnTo .= $separator.'_flarum_linked='.urlencode($provider);

            return $this->makeLoggedInResponse($user, $returnTo);
        }

        $payload = array_merge(
            ['suggested' => $registration->getSuggested()],
            (array) $registration->getPayload()
        );

        $token = RegistrationToken::generate($provider, $identifier, $provided, $payload);
        $token->save();

        return $this->makeRegistrationResponse($token->token, $returnTo);
    }

    /**
     * Build a redirect response for a successfully authenticated existing user.
     * Sets the remember-me cookie so the session is established on the next request.
     *
     * Override this method to customise the login redirect or cookie behaviour.
     */
    protected function makeLoggedInResponse(User $user, string $returnTo): ResponseInterface
    {
        $token = RememberAccessToken::generate($user->id);

        $response = new RedirectResponse($returnTo ?: '/');

        return $this->rememberer->remember($response, $token);
    }

    /**
     * Build a redirect response that triggers the registration modal on the frontend.
     *
     * The `_flarum_auth` query parameter carries the registration token. The frontend
     * detects this parameter on boot, strips it from the URL, and opens the SignUpModal
     * pre-populated with data from the provider.
     *
     * Override this method to customise how the registration handoff is communicated
     * to the frontend.
     */
    protected function makeRegistrationResponse(string $token, string $returnTo): ResponseInterface
    {
        $base = $returnTo ?: '/';

        // Append _flarum_auth without clobbering any existing query params on returnTo.
        $separator = str_contains($base, '?') ? '&' : '?';

        return new RedirectResponse($base.$separator.'_flarum_auth='.urlencode($token));
    }
}
