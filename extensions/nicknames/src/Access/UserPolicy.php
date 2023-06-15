<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Access;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class UserPolicy extends AbstractPolicy
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function editNickname(User $actor, User $user): ?string
    {
        if ($actor->isGuest() && ! $user->exists && $this->settings->get('flarum-nicknames.set_on_registration')) {
            return $this->allow();
        } elseif ($actor->id === $user->id && $actor->hasPermission('user.editOwnNickname')) {
            return $this->allow();
        } elseif ($actor->can('edit', $user)) {
            return $this->allow();
        }

        return null;
    }
}
