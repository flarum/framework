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

class PostRepository
{
    /**
     * Get a new query builder for the posts table.
     *
     * @return Builder<Post>
     */
    public function query()
    {
        return Post::query();
    }

    /**
     * @param User|null $user
     * @return Builder<Post>
     */
    public function queryVisibleTo(?User $user = null)
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
     * @param int $id
     * @param \Flarum\User\User|null $actor
     * @return Post
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        return $this->queryVisibleTo($actor)->findOrFail($id);
    }

    /**
     * Find posts that match certain conditions, optionally making sure they
     * are visible to a certain user, and/or using other criteria.
     *
     * @param array $where
     * @param \Flarum\User\User|null $actor
     * @param array $sort
     * @param int $count
     * @param int $start
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhere(array $where = [], User $actor = null, $sort = [], $count = null, $start = 0)
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
     * @param array $ids
     * @param User $actor
     * @return array
     */
    public function filterVisibleIds(array $ids, User $actor)
    {
        return $this->queryIds($ids, $actor)->pluck('posts.id')->all();
    }

    /**
     * Get the position within a discussion where a post with a certain number
     * is. If the post with that number does not exist, the index of the
     * closest post to it will be returned.
     *
     * @param int $discussionId
     * @param int $number
     * @param \Flarum\User\User|null $actor
     * @return int
     */
    public function getIndexForNumber($discussionId, $number, User $actor = null)
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
     * @param array $ids
     * @param User|null $actor
     * @return Builder<Post>
     */
    protected function queryIds(array $ids, User $actor = null)
    {
        return $this->queryVisibleTo($actor)->whereIn('posts.id', $ids);
    }
}
