<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Access;

use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeFlagVisibility
{
    /**
     * @param User $actor
     * @param Builder $query
     */
    public function __invoke(User $actor, Builder $query)
    {
        $query
            ->select('flags.*')
            ->leftJoin('posts', 'posts.id', '=', 'flags.post_id')
            ->leftJoin('discussions', 'discussions.id', '=', 'posts.discussion_id')
            ->whereNotExists(function ($query) use ($actor) {
                return $query->selectRaw('1')
                    ->from('discussion_tag')
                    ->whereIn('tag_id', Tag::getIdsWhereCannot($actor, 'discussion.viewFlags'))
                    ->whereColumn('discussions.id', 'discussion_id');
            });
    }
}
