<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Access;

use Flarum\Tags\Tag;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class TagPolicy extends AbstractPolicy
{
    public function can(User $actor, string $ability, Tag $tag): string|bool|null
    {
        if ($tag->parent_id !== null && ! $actor->can($ability, $tag->parent)) {
            return $this->deny();
        }

        if ($tag->is_restricted) {
            $id = $tag->id;

            return $actor->hasPermission("tag$id.$ability");
        }

        return null;
    }

    public function addToDiscussion(User $actor, Tag $tag): bool
    {
        return $actor->can('startDiscussion', $tag);
    }
}
