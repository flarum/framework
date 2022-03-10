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
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param User $actor
     * @param User $user
     * @return bool|null
     */
    public function editNickname(User $actor, User $user)
    {
        if ($actor->isGuest() && !$user->exists && $this->settings->get('flarum-nicknames.set_on_registration')) {
            return $this->allow();
        } else if ($actor->id === $user->id && $actor->hasPermission('user.editOwnNickname')) {
            return $this->allow();
        } else if ($actor->can('edit', $user)) {
            return $this->allow();
        }
    }
}
