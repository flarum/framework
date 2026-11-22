<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\User\User;

class UserResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Integer::make('likeCount')
                ->description('Number of posts the user has liked. Only present for the user themselves and for moderators.')
                ->visible(fn (User $user, Context $context) => self::visibleTo($context->getActor(), $user)),
        ];
    }

    /**
     * The stored total counts every like the user has on a comment post,
     * including likes on posts the reader may not be allowed to see: posts in
     * private discussions, hidden posts, and posts in restricted tags. The
     * Likes tab filters through post visibility, so a public number would let
     * anyone compare the badge with the list (or watch it change) and learn
     * that the user is active where they cannot go.
     *
     * So the number is a moderation read: the user whose profile it is and
     * holders of the global `discussion.hidePosts` permission (moderators, and
     * admins, for whom hasPermission() is always true) are told it. Everyone
     * else gets no attribute at all, rather than a number.
     */
    public static function visibleTo(User $actor, User $user): bool
    {
        return $actor->id === $user->id || $actor->hasPermission('discussion.hidePosts');
    }
}
