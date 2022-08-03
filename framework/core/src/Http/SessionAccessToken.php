<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

class SessionAccessToken extends AccessToken
{
    public static $type = 'session';

    protected static $lifetime = 60 * 60;  // 1 hour

    protected $hidden = ['token'];
}
