<?php namespace Flarum\Admin\Middleware;

use Flarum\Core\Support\Actor;
use Flarum\Core\Models\AccessToken;
use Closure;

class LoginWithCookieAndCheckAdmin
{
    protected $actor;

    public function __construct(Actor $actor)
    {
        $this->actor = $actor;
    }

    public function handle($request, Closure $next)
    {
        if (($token = $request->cookie('flarum_remember')) &&
            ($accessToken = AccessToken::where('id', $token)->first()) &&
            $accessToken->user->isAdmin()) {
            $this->actor->setUser($accessToken->user);
        } else {
            die('ur not an admin');
        }

        return $next($request);
    }
}
