<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Event;

use Flarum\Http\AccessToken;

class DeveloperTokenCreated
{
    public function __construct(
        public AccessToken $token
    ) {
    }
}
