<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Access;

use Flarum\Discussion\Discussion;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class DiscussionPolicy extends AbstractPolicy
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function can(User $actor, string $ability)
    {
        if ($actor->hasPermission('discussion.'.$ability)) {
            return $this->allow();
        }
    }

    public function rename(User $actor, Discussion $discussion)
    {
        if ($discussion->user_id == $actor->id && $actor->can('reply', $discussion)) {
            $allowRenaming = $this->settings->get('allow_renaming');

            if ($allowRenaming === '-1'
                || ($allowRenaming === 'reply' && $discussion->participant_count <= 1)
                || (is_numeric($allowRenaming) && $discussion->created_at->diffInMinutes() < $allowRenaming)) {
                return $this->allow();
            }
        }
    }

    public function hide(User $actor, Discussion $discussion)
    {
        if ($discussion->user_id == $actor->id
            && $discussion->participant_count <= 1
            && (! $discussion->hidden_at || $discussion->hidden_user_id == $actor->id)
            && $actor->can('reply', $discussion)
        ) {
            return $this->allow();
        }
    }
}
