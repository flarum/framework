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
            ($accessToken = AccessToken::where('id', $token)->first())) {
            $user = $accessToken->user;
            if (! $user->isAdmin()) {
                die('ur not an admin');
            }
            $this->actor->setUser($user);
        }

        return $next($request);
    }
}
