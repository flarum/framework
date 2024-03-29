<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

class RememberAccessToken extends AccessToken
{
    public static string $type = 'session_remember';

    protected static int $lifetime = 5 * 365 * 24 * 60 * 60; // 5 years

    protected $hidden = ['token'];

    /**
     * Just a helper method so we can re-use the lifetime value which is protected.
     */
    public static function rememberCookieLifeTime(): int
    {
        return self::$lifetime;
    }
}
