<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Actions;

use Flarum\Core\Users\User;
use Zend\Diactoros\Response\HtmlResponse;
use Flarum\Api\Commands\GenerateAccessToken;
use Flarum\Core\Users\AuthToken;

trait ExternalAuthenticatorTrait
{
    use WritesRememberCookie;

    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * Respond with JavaScript to inform the Flarum app about the user's
     * authentication status.
     *
     * An array of identification attributes must be passed as the first
     * argument. These are checked against existing user accounts; if a match is
     * found, then the user is authenticated and logged into that account via
     * cookie. The Flarum app will then simply refresh the page.
     *
     * If no matching account is found, then an AuthToken will be generated to
     * store the identification attributes. This token, along with an optional
     * array of suggestions, will be passed into the Flarum app's sign up modal.
     * This results in the user not having to choose a password. When they
     * complete their registration, the identification attributes will be
     * set on their new user account.
     *
     * @param array $identification
     * @param array $suggestions
     * @return HtmlResponse
     */
    protected function authenticated(array $identification, array $suggestions = [])
    {
        $user = User::where($identification)->first();

        // If a user with these attributes already exists, then we will log them
        // in by generating an access token. Otherwise, we will generate a
        // unique token for these attributes and add it to the response, along
        // with the suggested account information.
        if ($user) {
            $accessToken = $this->bus->dispatch(new GenerateAccessToken($user->id));

            $payload = ['authenticated' => true];
        } else {
            $token = AuthToken::generate($identification);
            $token->save();

            $payload = array_merge($identification, $suggestions, ['token' => $token->id]);
        }

        $content = sprintf('<script>
window.opener.app.authenticationComplete(%s);
window.close();
</script>', json_encode($payload));

        $response = new HtmlResponse($content);

        if (isset($accessToken)) {
            $response = $this->withRememberCookie($response, $accessToken->id);
        }

        return $response;
    }
}
