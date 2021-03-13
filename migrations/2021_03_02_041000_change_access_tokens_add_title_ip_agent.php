<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addColumns('access_tokens', [
    'title' => ['string', 'length' => 150, 'nullable' => true],
    // Accommodates both IPv4 and IPv6 as strings
    'last_ip_address' => ['string', 'length' => 45, 'nullable' => true],
    // Technically, there's no limit to a user agent length
    // Most are around 150 in length, and the general recommendation seems to be below 200
    // We're going to use the longest string possible to be safe
    // There will still be exceptions, we'll just truncate them
    'last_user_agent' => ['string', 'length' => 255, 'nullable' => true],
]);
