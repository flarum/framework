<?php namespace Flarum\Admin\Middleware;

use Flarum\Support\Actor;
use Flarum\Core\Models\AccessToken;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class LoginWithCookieAndCheckAdmin implements MiddlewareInterface
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
        $cookies = $request->getCookieParams();

        if (($token = $cookies['flarum_remember']) &&
            ($accessToken = AccessToken::where('id', $token)->first()) &&
            $accessToken->user->isAdmin()
        ) {
            $this->actor->setUser($accessToken->user);
        } else {
            die('ur not an admin');
        }

        return $out ? $out($request, $response) : $response;
    }
}
