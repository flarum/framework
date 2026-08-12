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
        protected CookieFactory $cookie,
        protected SessionConfig $sessionConfig
    ) {
    }

    /**
     * Sets the remember cookie on a response.
     */
    public function remember(ResponseInterface $response, #[\SensitiveParameter] RememberAccessToken $token): ResponseInterface
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->make(self::COOKIE_NAME, $token->token, $this->cookieLifetime())
        );
    }

    /**
     * How long the browser should keep the remember cookie, or `null` to keep
     * it only until the browser closes.
     */
    protected function cookieLifetime(): ?int
    {
        if ($this->sessionConfig->cookieExpiresOnClose()) {
            return null;
        }

        return RememberAccessToken::lifetime();
    }

    public function forget(ResponseInterface $response): ResponseInterface
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->expire(self::COOKIE_NAME)
        );
    }
}
