<?php namespace Flarum\Api\Middleware;

use Flarum\Core\Models\AccessToken;
use Flarum\Support\Actor;
use Closure;

class LoginWithHeader
{
    protected $actor;

    protected $prefix = 'Token ';

    public function __construct(Actor $actor)
    {
        $this->actor = $actor;
    }

    public function handle($request, Closure $next)
    {
        $header = $request->headers->get('authorization');
        if (starts_with($header, $this->prefix) &&
            ($token = substr($header, strlen($this->prefix))) &&
            ($accessToken = AccessToken::where('id', $token)->first())) {
            $this->actor->setUser($user = $accessToken->user);

            $user->updateLastSeen()->save();
        }

        return $next($request);
    }
}
