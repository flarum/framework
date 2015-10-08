<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Flarum\Api\AccessToken;
use Flarum\Api\ApiKey;
use Flarum\Core\Guest;
use Flarum\Core\User;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class AuthenticateWithHeader implements MiddlewareInterface
{
    /**
     * @var Container
     */
    protected $app;

    /**
     * @var string
     */
    protected $prefix = 'Token ';

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
        $request = $this->logIn($request);

        return $out ? $out($request, $response) : $response;
    }

    /**
     * @param Request $request
     * @return Request
     */
    protected function logIn(Request $request)
    {
        $header = $request->getHeaderLine('authorization');

        $parts = explode(';', $header);

        if (isset($parts[0]) && starts_with($parts[0], $this->prefix)) {
            $token = substr($parts[0], strlen($this->prefix));

            if (($accessToken = AccessToken::find($token)) && $accessToken->isValid()) {
                $user = $accessToken->user;

                $user->updateLastSeen()->save();

                return $request->withAttribute('actor', $user);
            } elseif (isset($parts[1]) && ($apiKey = ApiKey::valid($token))) {
                $userParts = explode('=', trim($parts[1]));

                if (isset($userParts[0]) && $userParts[0] === 'userId') {
                    return $request->withAttribute('actor', $user = User::find($userParts[1]));
                }
            }
        }

        return $request->withAttribute('actor', new Guest);
    }
}
