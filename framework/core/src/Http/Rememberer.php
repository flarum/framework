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
    public const COOKIE_NAME = 'remember';

    public function __construct(
        protected CookieFactory $cookie
    ) {
    }

    /**
     * Sets the remember cookie on a response.
     *
     * The lifetime comes off the token's own class, so a subclass declaring a
     * different `$lifetime` gets a cookie that expires with the token instead of
     * one outliving it.
     */
    public function remember(ResponseInterface $response, #[\SensitiveParameter] RememberAccessToken $token): ResponseInterface
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->make(self::COOKIE_NAME, $token->token, $token::rememberCookieLifeTime())
        );
    }

    public function forget(ResponseInterface $response): ResponseInterface
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->expire(self::COOKIE_NAME)
        );
    }
}
