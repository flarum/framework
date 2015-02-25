<?php namespace Flarum\Web\Actions;

use Illuminate\Http\Request;
use Flarum\Web\Events\UserLoggedOut;
use Cookie;

class LogoutAction extends Action
{
    public function handle(Request $request, $params = [])
    {
        $user = $this->actor->getUser();

        if ($user->exists) {
            $user->accessTokens()->delete();

            event(new UserLoggedOut($user));
        }

        return redirect('')->withCookie($this->makeForgetCookie());
    }

    public function makeForgetCookie()
    {
        return Cookie::forget('flarum_remember');
    }
}
