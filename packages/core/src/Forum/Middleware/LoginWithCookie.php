<?php namespace Flarum\Forum\Middleware;

use Flarum\Support\Actor;
use Flarum\Core\Models\AccessToken;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class LoginWithCookie implements MiddlewareInterface
{
    /**
     * @var Actor
     */
    protected $actor;

    public function __construct(Actor $actor)
    {
        $this->actor = $actor;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        if (($token = array_get($request->getCookieParams(), 'flarum_remember')) &&
            ($accessToken = AccessToken::where('id', $token)->first())
        ) {
            $this->actor->setUser($user = $accessToken->user);

            $user->updateLastSeen()->save();
        }

        return $out ? $out($request, $response) : $response;
    }
}
