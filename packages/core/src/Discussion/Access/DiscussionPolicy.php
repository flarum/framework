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
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionPolicy extends AbstractPolicy
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     * @param Dispatcher $events
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param User $actor
     * @param string $ability
     * @return bool|null
     */
    public function can(User $actor, $ability)
    {
        if ($actor->hasPermission('discussion.'.$ability)) {
            return $this->allow();
        }
    }

    /**
     * @param User $actor
     * @param \Flarum\Discussion\Discussion $discussion
     * @return bool|null
     */
    public function rename(User $actor, Discussion $discussion)
    {
        if ($discussion->user_id == $actor->id && $actor->can('reply', $discussion)) {
            $allowRenaming = $this->settings->get('allow_renaming');

            if ($allowRenaming === '-1'
                || ($allowRenaming === 'reply' && $discussion->participant_count <= 1)
                || ($discussion->created_at->diffInMinutes() < $allowRenaming)) {
                return $this->allow();
            }
        }
    }

    /**
     * @param User $actor
     * @param \Flarum\Discussion\Discussion $discussion
     * @return bool|null
     */
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
