<?php namespace Flarum\Forum\Actions;

use Flarum\Forum\Events\UserLoggedOut;
use Flarum\Support\Action;
use Psr\Http\Message\ServerRequestInterface as Request;

class LogoutAction extends Action
{
    use WritesRememberCookie;

    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Request $request, array $routeParams = [])
    {
        $user = app('flarum.actor');

        if ($user->exists) {
            $user->accessTokens()->delete();

            event(new UserLoggedOut($user));
        }

        return $this->withForgetCookie($this->redirectTo('/'));
    }
}
