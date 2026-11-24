<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions;

use Flarum\Post\Post;
use Flarum\User\User;

/**
 * Collects the users whose mention count one request changed, for the update
 * response.
 *
 * A single save can raise more than one of the events that change mentions: a
 * combined content and visibility save revises the post and then hides (or
 * restores) it, and each listener finds users to recount. Both add their users
 * to the post, so the response reports all of them instead of only the ones
 * the last listener saw.
 *
 * The listeners run after the save, so a later one cannot change the count of
 * a user an earlier one already reported: those models stay as they are. Only
 * the users not in the set yet are queried. Keeping the earlier models is
 * sound because the post resource applies the content field before the
 * visibility ones and dispatches the post's events only after the whole save,
 * so a user the content listener reports already carries the post's final
 * visibility.
 */
class AffectedMentionUsers
{
    /**
     * Add users to the set the post's response reports, keeping the ones an
     * earlier listener added.
     *
     * @param array<int|string> $userIds
     */
    public static function add(Post $post, array $userIds, User $actor): void
    {
        if (empty($userIds)) {
            return;
        }

        $reported = $post->relationLoaded('affectedMentionUsers')
            ? $post->getRelation('affectedMentionUsers')
            : null;

        $new = array_values(array_diff(
            array_unique(array_map('intval', $userIds)),
            $reported?->modelKeys() ?? []
        ));

        if (empty($new)) {
            return;
        }

        $added = User::query()
            ->whereKey($new)
            ->whereVisibleTo($actor)
            ->with('groups')
            ->get();

        $post->setRelation('affectedMentionUsers', $reported ? $reported->merge($added) : $added);
    }
}
