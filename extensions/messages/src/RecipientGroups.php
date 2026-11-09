<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Group\Group;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

class RecipientGroups
{
    public const SETTING = 'flarum-messages.recipient_groups';
    public const PERMISSION = 'dialog.messageGroups';
    public const LIMIT = 10;

    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function ids(): array
    {
        $ids = json_decode($this->settings->get(self::SETTING, '[]'), true);

        return is_array($ids) ? array_values(array_diff(array_map('intval', $ids), [Group::GUEST_ID, Group::MEMBER_ID])) : [];
    }

    public function canAddress(User $actor, Group $group): bool
    {
        return $actor->can('sendAnyMessage') && $actor->hasPermission(self::PERMISSION)
            && in_array($group->id, $this->ids(), true)
            && (! $group->is_hidden || $actor->can('viewHiddenGroups'));
    }
}
