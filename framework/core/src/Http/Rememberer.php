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

    /**
     * How long the cookie lasts for a token that never expires, in seconds.
     */
    public const NEVER_EXPIRING_COOKIE_LIFETIME = 5 * 365 * 24 * 60 * 60;

    public function __construct(
        protected CookieFactory $cookie,
        protected SessionConfig $sessionConfig
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
            $this->cookie->make(self::COOKIE_NAME, $token->token, $this->cookieLifetime($token))
        );
    }

    /**
     * How long the browser should keep the remember cookie, or `null` to keep
     * it only until the browser closes.
     *
     * A token lifetime of zero means the token never expires, but a cookie
     * max-age of zero would make it a session cookie, so those get a long one.
     */
    protected function cookieLifetime(RememberAccessToken $token): ?int
    {
        if ($this->sessionConfig->cookieExpiresOnClose()) {
            return null;
        }

        return $token::lifetime() ?: self::NEVER_EXPIRING_COOKIE_LIFETIME;
    }

    public function forget(ResponseInterface $response): ResponseInterface
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->expire(self::COOKIE_NAME)
        );
    }
}
