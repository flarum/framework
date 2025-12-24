<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class UserPolicy extends AbstractPolicy
{
    public array $reservedAbilities;

    public function __construct()
    {
        $this->reservedAbilities = resolve('gdpr.user.reservedAbilities');
    }

    public function can(User $actor, string $ability, mixed $user): ?string
    {
        // if $user is anonymized, deny all abilities except those in $reservedAbilities
        if ($user instanceof User
            && $user->anonymized
            && ! in_array($ability, $this->reservedAbilities)) {
            return $this->deny();
        }

        return null;
    }

    public function exportFor(User $actor, User $user): string|bool
    {
        if ($actor->is($user)) {
            return $this->allow();
        }

        if ($user->anonymized) {
            return $this->deny();
        }

        return $actor->hasPermission('moderateExport');
    }
}
