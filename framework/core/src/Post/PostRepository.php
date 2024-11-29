<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PostRepository
{
    /**
     * @return Builder<Post>
     */
    public function query(): Builder
    {
        return Post::query();
    }

    /**
     * @return Builder<Post>
     */
    public function queryVisibleTo(?User $user = null): Builder
    {
        $query = $this->query();

        if ($user !== null) {
            $query->whereVisibleTo($user);
        }

        return $query;
    }

    /**
     * Find a post by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, ?User $actor = null): Post
    {
        return $this->queryVisibleTo($actor)->findOrFail($id);
    }

    /**
     * Find posts that match certain conditions, optionally making sure they
     * are visible to a certain user, and/or using other criteria.
     */
    public function findWhere(array $where = [], ?User $actor = null, array $sort = [], ?int $count = null, int $start = 0): Collection
    {
        $query = $this->queryVisibleTo($actor)
            ->where($where)
            ->skip($start)
            ->take($count);

        foreach ((array) $sort as $field => $order) {
            $query->orderBy($field, $order);
        }

        return $query->get();
    }

    /**
     * Filter a list of post IDs to only include posts that are visible to a
     * certain user.
     *
     * @param int[] $ids
     */
    public function filterVisibleIds(array $ids, User $actor): array
    {
        return $this->queryIds($ids, $actor)->pluck('posts.id')->all();
    }

    /**
     * Get the position within a discussion where a post with a certain number
     * is. If the post with that number does not exist, the index of the
     * closest post to it will be returned.
     */
    public function getIndexForNumber(int $discussionId, int $number, ?User $actor = null): int
    {
        if (! ($discussion = Discussion::find($discussionId))) {
            return 0;
        }

        $query = $discussion->posts()
            ->whereVisibleTo($actor)
            ->where('created_at', '<', function ($query) use ($discussionId, $number) {
                $query->select('created_at')
                    ->from('posts')
                    ->where('discussion_id', $discussionId)
                    ->whereNotNull('number')
                    ->take(1)

                    // We don't add $number as a binding because for some
                    // reason doing so makes the bindings go out of order.
                    ->orderByRaw('ABS(CAST(number AS SIGNED) - '.(int) $number.')');
            });

        return $query->count();
    }

    /**
     * @param int[] $ids
     * @return Builder<Post>
     */
    protected function queryIds(array $ids, ?User $actor = null): Builder
    {
        return $this->queryVisibleTo($actor)->whereIn('posts.id', $ids);
    }
}
