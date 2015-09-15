<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Middleware;

use Flarum\Api\AccessToken;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class LoginWithCookie implements MiddlewareInterface
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
        $this->logIn($request);

        return $out ? $out($request, $response) : $response;
    }

    /**
     * Set the application's actor instance according to the request token.
     *
     * @param Request $request
     * @return bool
     */
    protected function logIn(Request $request)
    {
        if ($token = $this->getToken($request)) {
            if (! $token->isValid()) {
                // TODO: https://github.com/flarum/core/issues/253
            } elseif ($token->user) {
                $this->app->instance('flarum.actor', $user = $token->user);

                $user->updateLastSeen()->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Get the access token referred to by the request cookie.
     *
     * @param Request $request
     * @return AccessToken|null
     */
    protected function getToken(Request $request)
    {
        $token = array_get($request->getCookieParams(), 'flarum_remember');

        if ($token) {
            return AccessToken::find($token);
        }
    }
}
