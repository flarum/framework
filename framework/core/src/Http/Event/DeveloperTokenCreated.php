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
    /**
     * @var AccessToken
     */
    public $token;

    public function __construct(AccessToken $token)
    {
        $this->token = $token;
    }
}
