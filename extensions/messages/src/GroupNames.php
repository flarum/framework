<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Messages\Access\MessagingPermission;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

/**
 * Names members give groups, where an admin allows it. Turning it off hides
 * the names given so far, rather than forgetting them.
 */
class GroupNames
{
    public const SETTING = 'flarum-messages.allow_group_names';

    public const MAX_LENGTH = 50;

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function allowed(): bool
    {
        return (bool) $this->settings->get(self::SETTING);
    }

    /** The name a group goes by, if it has one and names are allowed. */
    public function of(Dialog $dialog): ?string
    {
        return $this->allowed() && $dialog->isGroup() ? $dialog->name : null;
    }

    /**
     * Whether the actor may name a group they are a member of: anyone who
     * may write in it. Membership is the caller's to check, as it is for
     * MessagingPermission::canSendIn().
     */
    public function canRename(User $actor, Dialog $dialog): bool
    {
        return $this->allowed() && $dialog->isGroup() && MessagingPermission::canSendIn($actor, $dialog);
    }
}
