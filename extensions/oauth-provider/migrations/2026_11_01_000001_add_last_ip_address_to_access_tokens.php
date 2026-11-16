<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addColumns('oauth_provider_access_tokens', [
    // Where the token was last used from, recorded with last_used_at.
    'last_ip_address' => ['string', 'length' => 45, 'nullable' => true],
]);
