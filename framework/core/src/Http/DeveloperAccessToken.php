<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

/**
 * @inheritDoc
 */
class DeveloperAccessToken extends AccessToken
{
    public static $type = 'developer';

    protected static $lifetime = 0;
}
