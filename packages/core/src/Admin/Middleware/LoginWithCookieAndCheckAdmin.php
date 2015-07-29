<?php namespace Flarum\Admin\Middleware;

use Flarum\Api\AccessToken;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class LoginWithCookieAndCheckAdmin implements MiddlewareInterface
{
    /**
     * @var Container
     */
    protected $app;

    /**
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        if (($token = array_get($request->getCookieParams(), 'flarum_remember')) &&
            ($accessToken = AccessToken::where('id', $token)->first()) &&
            $accessToken->user->isAdmin()
        ) {
            $this->app->instance('flarum.actor', $accessToken->user);
        } else {
            die('Access Denied');
        }

        return $out ? $out($request, $response) : $response;
    }
}
