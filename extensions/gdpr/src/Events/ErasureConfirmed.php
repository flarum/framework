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
 * Dispatched when a user confirms their pending erasure request (e.g. via the
 * emailed confirmation link). The `actor` is the user who confirmed.
 */
class ErasureConfirmed
{
    public function __construct(
        public User $actor,
        public ErasureRequest $request
    ) {
    }
}
