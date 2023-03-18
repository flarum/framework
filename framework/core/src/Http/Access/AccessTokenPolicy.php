<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Access;

use Flarum\Http\AccessToken;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class AccessTokenPolicy extends AbstractPolicy
{
    public function revoke(User $actor, AccessToken $token)
    {
        if ($token->user_id === $actor->id || $actor->hasPermission('moderateAccessTokens')) {
            return $this->allow();
        }
    }
}
