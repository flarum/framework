<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Access;

use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class ErasureRequestPolicy extends AbstractPolicy
{
    public function process(User $actor, ErasureRequest $request): ?string
    {
        if ($actor->hasPermission('processErasure') && $request->isConfirmed() && !$request->isCancelled()) {
            return $this->allow();
        }

        return null;
    }

    public function cancel(User $actor, ErasureRequest $request): ?string
    {
        if (($actor->id === $request->user_id || $actor->hasPermission('processErasure'))
            && in_array($request->status, ['awaiting_user_confirmation', 'user_confirmed'])
        ) {
            return $this->allow();
        }

        return null;
    }
}
