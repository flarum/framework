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
     * @param RememberAccessToken $token The remember token to set on the response.
     * @return ResponseInterface
     */
    public function remember(ResponseInterface $response, RememberAccessToken $token)
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->make(self::COOKIE_NAME, $token->token, RememberAccessToken::rememberCookieLifeTime())
        );
    }

    public function forget(ResponseInterface $response)
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->expire(self::COOKIE_NAME)
        );
    }
}
