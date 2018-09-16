<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Access;

use Flarum\Flags\Flag;
use Flarum\Tags\Tag;
use Flarum\User\AbstractPolicy;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

class FlagPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Flag::class;

    /**
     * @param User $actor
     * @param Builder $query
     */
    public function find(User $actor, Builder $query)
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
