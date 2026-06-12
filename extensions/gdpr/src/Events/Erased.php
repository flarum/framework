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

class Erased
{
    public function __construct(
        public string $username,
        public string $email,
        public string $mode,
        public User $user,
        // The originating erasure request, carrying who processed it (processed_by) — null for
        // the scheduled/system path. Optional so existing listeners that construct this event
        // without it keep working.
        public ?ErasureRequest $request = null
    ) {
    }
}
