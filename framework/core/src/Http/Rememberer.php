<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Symfony\Component\HttpFoundation\Response;

class Rememberer
{
    const COOKIE_NAME = 'remember';

    public function __construct(
        protected CookieFactory $cookie
    ) {
    }

    /**
     * Sets the remember cookie on a response.
     */
    public function remember(Response $response, RememberAccessToken $token): Response
    {
        $response->headers->setCookie(
            $this->cookie->make(self::COOKIE_NAME, $token->token, RememberAccessToken::rememberCookieLifeTime())
        );

        return $response;
    }

    public function forget(Response $response): Response
    {
        $response->headers->setCookie(
            $this->cookie->expire(self::COOKIE_NAME)
        );

        return $response;
    }
}
