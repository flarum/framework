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
 * Dispatched when an erasure request is cancelled. The `actor` is whoever
 * cancelled it (the requesting user, or an admin acting on their behalf).
 */
class ErasureCancelled
{
    public function __construct(
        public User $actor,
        public ErasureRequest $request
    ) {
    }
}
