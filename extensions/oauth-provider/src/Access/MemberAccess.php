<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Access;

use Flarum\User\User;

/**
 * Whether a member may use applications connected to their account: the
 * `oauthProvider.connect` permission, which members have by default.
 *
 * Suspended members, and anonymised ones (their email is no longer
 * confirmed), hold only guest permissions, so they can't connect anything,
 * and what they connected stops working until they may again.
 */
class MemberAccess
{
    public const string PERMISSION = 'oauthProvider.connect';

    public function allows(?User $user): bool
    {
        return $user !== null && ! $user->isGuest() && $user->can(self::PERMISSION);
    }

    public function allowsUserId(int|string|null $userId): bool
    {
        return $userId !== null && $this->allows(User::query()->find($userId));
    }
}
