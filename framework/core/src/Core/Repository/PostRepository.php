<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Repository;

use Flarum\Core\Post;
use Flarum\Event\ScopePostVisibility;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\User;
use Flarum\Core\Discussion;

class PostRepository
{
    /**
     * Find a post by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param integer $id
     * @param \Flarum\Core\User $actor
     * @return \Flarum\Core\Post
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null)
    {
        $posts = $this->findByIds([$id], $actor);

        if (! count($posts)) {
            throw new ModelNotFoundException;
        }

        return $posts->first();
    }

    /**
     * Find posts that match certain conditions, optionally making sure they
     * are visible to a certain user, and/or using other criteria.
     *
     * @param array $where
     * @param \Flarum\Core\User|null $actor
     * @param array $sort
     * @param integer $count
     * @param integer $start
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhere(array $where = [], User $actor = null, $sort = [], $count = null, $start = 0)
    {
        $query = Post::where($where)
            ->skip($start)
            ->take($count);

        foreach ((array) $sort as $field => $order) {
            $query->orderBy($field, $order);
        }

        $ids = $query->lists('id')->all();

        return $this->findByIds($ids, $actor);
    }

    /**
     * Find posts by their IDs, optionally making sure they are visible to a
     * certain user.
     *
     * @param array $ids
     * @param \Flarum\Core\User|null $actor
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByIds(array $ids, User $actor = null)
    {
        $discussions = $this->getDiscussionsForPosts($ids, $actor);

        $posts = Post::whereIn('id', $ids)
            ->where(function ($query) use ($discussions, $actor) {
                foreach ($discussions as $discussion) {
                    $query->orWhere(function ($query) use ($discussion, $actor) {
                        $query->where('discussion_id', $discussion->id);

                        event(new ScopePostVisibility($discussion, $query, $actor));
                    });
                }
            })
            ->get();

        $posts = $posts->sort(function ($a, $b) use ($ids) {
            $aPos = array_search($a->id, $ids);
            $bPos = array_search($b->id, $ids);

            if ($aPos === $bPos) {
                return 0;
            }

            return $aPos < $bPos ? -1 : 1;
        });

        return $posts;
    }

    /**
     * Get the position within a discussion where a post with a certain number
     * is. If the post with that number does not exist, the index of the
     * closest post to it will be returned.
     *
     * @param integer $discussionId
     * @param integer $number
     * @param \Flarum\Core\User|null $actor
     * @return integer
     */
    public function getIndexForNumber($discussionId, $number, User $actor = null)
    {
        $query = Discussion::find($discussionId)
            ->postsVisibleTo($actor)
            ->where('time', '<', function ($query) use ($discussionId, $number) {
                $query->select('time')
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

    protected function getDiscussionsForPosts($postIds, User $actor)
    {
        return Discussion::whereIn('id', function ($query) use ($postIds) {
            $query->select('discussion_id')->from('posts')->whereIn('id', $postIds);
        })
            ->whereVisibleTo($actor)
            ->get();
    }
}
