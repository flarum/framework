<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Dflydev\FigCookies\FigResponseCookies;
use Psr\Http\Message\ResponseInterface;

class Rememberer
{
    const COOKIE_NAME = 'remember';

    /**
     * @var CookieFactory
     */
    protected $cookie;

    /**
     * @param CookieFactory $cookie
     */
    public function __construct(CookieFactory $cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     * Sets the remember cookie on a response.
     * @param ResponseInterface $response
     * @param RememberAccessToken $token The remember token to set on the response. Use of non-remember token is deprecated in beta 16, removed eta 17.
     * @return ResponseInterface
     */
    public function remember(ResponseInterface $response, AccessToken $token)
    {
        if (! ($token instanceof RememberAccessToken)) {
            trigger_error('Parameter $token of type AccessToken is deprecated in beta 16, must be instance of RememberAccessToken in beta 17', E_USER_DEPRECATED);

            $token->type = 'session_remember';
            $token->save();
        }

        return FigResponseCookies::set(
            $response,
            $this->cookie->make(self::COOKIE_NAME, $token->token, RememberAccessToken::rememberCookieLifeTime())
        );
    }

    /**
     * @param ResponseInterface $response
     * @param $userId
     * @return ResponseInterface
     * @deprecated beta 16, removed beta 17. Use remember() with a token
     */
    public function rememberUser(ResponseInterface $response, $userId)
    {
        $token = RememberAccessToken::generate($userId);

        return $this->remember($response, $token);
    }

    public function forget(ResponseInterface $response)
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->expire(self::COOKIE_NAME)
        );
    }
}
