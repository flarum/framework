<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Access;

use Carbon\Carbon;
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

    public function can(User $actor, string $ability, Discussion $discussion): ?string
    {
        // Wrap all discussion permission checks with some logic pertaining to
        // the discussion's tags. If the discussion has a tag that has been
        // restricted, the user must have the permission for that tag.
        $tags = $discussion->tags;

        if (count($tags)) {
            $restrictedButHasAccess = false;

            foreach ($tags as $tag) {
                if ($tag->is_restricted) {
                    if (! $actor->hasPermission('tag'.$tag->id.'.discussion.'.$ability)) {
                        return $this->deny();
                    }

                    $restrictedButHasAccess = true;
                }
            }

            if ($restrictedButHasAccess) {
                return $this->allow();
            }
        }

        return null;
    }

    /**
     * Allow authors to rename their own discussion regardless of tag restrictions,
     * subject to the same global `allow_renaming` setting as the core policy.
     *
     * Without this, the `can()` catch-all would deny the rename ability for authors
     * in restricted tags because there is no tag-specific `discussion.rename` permission
     * to grant — it is an implicit "own" ability checked in core's DiscussionPolicy.
     * By returning a result here, `can()` is never reached for this case.
     *
     * We use `hasPermission('discussion.reply')` rather than `$actor->can('reply', $discussion)`
     * to avoid a circular denial: `can('reply', $discussion)` would go back through this same
     * policy's `can()` method, which would deny it for the same restricted-tag reason.
     */
    public function rename(User $actor, Discussion $discussion): ?string
    {
        if ($discussion->user_id == $actor->id && $actor->hasPermission('discussion.reply')) {
            $allowRenaming = $this->settings->get('allow_renaming');

            if ($allowRenaming === '-1'
                || ($allowRenaming === 'reply' && $discussion->participant_count <= 1)
                || (is_numeric($allowRenaming) && $discussion->created_at->diffInMinutes(null, true) < $allowRenaming)) {
                return $this->allow();
            }
        }

        return null;
    }

    /**
     * Allow authors to hide (delete) their own discussion regardless of tag restrictions,
     * subject to the same conditions as the core policy.
     *
     * We use `hasPermission('discussion.reply')` rather than `$actor->can('reply', $discussion)`
     * to avoid a circular denial: `can('reply', $discussion)` would go back through this same
     * policy's `can()` method, which would deny it for the same restricted-tag reason.
     *
     * @see \Flarum\Discussion\Access\DiscussionPolicy::hide()
     */
    public function hide(User $actor, Discussion $discussion): ?string
    {
        if ($discussion->user_id == $actor->id
            && $discussion->participant_count <= 1
            && (! $discussion->hidden_at || $discussion->hidden_user_id == $actor->id)
            && $actor->hasPermission('discussion.reply')
        ) {
            return $this->allow();
        }

        return null;
    }

    /**
     * This method checks, if the user is still allowed to edit the tags
     * based on the configuration item.
     */
    public function tag(User $actor, Discussion $discussion): ?string
    {
        if ($discussion->user_id == $actor->id && $actor->can('reply', $discussion)) {
            $allowEditTags = $this->settings->get('allow_tag_change');

            if (
                $allowEditTags === '-1'
                || ($allowEditTags === 'reply' && $discussion->participant_count <= 1)
                || (is_numeric($allowEditTags) && $discussion->created_at->diffInMinutes(new Carbon, true) < $allowEditTags)
            ) {
                return $this->allow();
            }
        }

        return null;
    }
}
