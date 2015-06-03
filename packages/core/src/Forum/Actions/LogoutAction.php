<?php namespace Flarum\Forum\Actions;

use Flarum\Forum\Events\UserLoggedOut;
use Psr\Http\Message\ServerRequestInterface as Request;

class LogoutAction extends BaseAction
{
    use WritesRememberCookie;

    public function handle(Request $request, $params = [])
    {
        $user = $this->actor->getUser();

        if ($user->exists) {
            $user->accessTokens()->delete();

            event(new UserLoggedOut($user));
        }

        return $this->withForgetCookie($this->redirectTo(''));
    }
}
