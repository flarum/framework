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

    /**
     * These are issued deliberately, to be used by a script or an integration
     * for as long as it needs them. An expiry set from the admin panel would
     * break those quietly, weeks later, so this type keeps its own answer.
     */
    protected static bool $configurableLifetime = false;
}
