<?php namespace Flarum\Web\Actions;

use Cookie;

class LoginAction extends Action
{
    public function respond(Request $request, $params = [])
    {
        Auth::user()->accessTokens()->delete();

        $this->event(new UserLoggedOut(Auth::user()));

        return Redirect::to('')->withCookie($this->makeForgetCookie());
    }

    public function makeForgetCookie()
    {
        return Cookie::forget('flarum_remember');
    }
}
