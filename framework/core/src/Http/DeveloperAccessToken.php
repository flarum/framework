<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

class DeveloperAccessToken extends AccessToken
{
    public static string $type = 'developer';

    protected static int $lifetime = 0;
}
