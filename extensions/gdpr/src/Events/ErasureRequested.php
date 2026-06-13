<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Events;

use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\User\User;

/**
 * Dispatched when a user requests erasure of their own data (before any
 * confirmation or processing). The `actor` is the user who made the request.
 */
class ErasureRequested
{
    public function __construct(
        public User $actor,
        public ErasureRequest $request
    ) {
    }
}
