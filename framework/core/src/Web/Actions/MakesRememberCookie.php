<?php namespace Flarum\Web\Actions;

use Cookie;

trait MakesRememberCookie
{
    protected function makeRememberCookie($token)
    {
        return Cookie::forever('flarum_remember', $token);
    }
}
