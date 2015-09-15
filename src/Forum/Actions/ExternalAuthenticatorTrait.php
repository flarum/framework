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
use Flarum\Core\Users\EmailToken;

trait ExternalAuthenticatorTrait
{
    use WritesRememberCookie;

    /**
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * Respond with JavaScript to tell the Flarum app that the user has been
     * authenticated, or with information about their sign up status.
     *
     * @param string $email The email of the user's account.
     * @param string $username A suggested username for the user's account.
     * @return HtmlResponse
     */
    protected function authenticated($email, $username)
    {
        $user = User::where('email', $email)->first();

        // If a user with this email address doesn't already exist, then we will
        // generate a unique confirmation token for this email address and add
        // it to the response, along with the email address and a suggested
        // username. Otherwise, we will log in the existing user by generating
        // an access token.
        if (! $user) {
            $token = EmailToken::generate($email);
            $token->save();

            $payload = compact('email', 'username');

            $payload['token'] = $token->id;
        } else {
            $accessToken = $this->bus->dispatch(new GenerateAccessToken($user->id));

            $payload = ['authenticated' => true];
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
