<?php namespace Flarum\Core\Repositories;

use Flarum\Core\Models\User;

interface PostRepositoryInterface
{
    /**
     * Find a post by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param  integer  $id
     * @param  \Flarum\Core\Models\User  $user
     * @return \Flarum\Core\Models\Post
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $user = null);

    /**
     * Find posts that match certain conditions, optionally making sure they
     * are visible to a certain user, and/or using other criteria.
     *
     * @param  array  $where
     * @param  \Flarum\Core\Models\User|null  $user
     * @param  string  $sort
     * @param  string  $order
     * @param  integer  $count
     * @param  integer  $start
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhere($where = [], User $user = null, $sort = 'time', $order = 'asc', $count = null, $start = 0);

    /**
     * Find posts by their IDs, optionally making sure they are visible to a
     * certain user.
     *
     * @param  array  $ids
     * @param  \Flarum\Core\Models\User|null  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByIds(array $ids, User $user = null);

    /**
     * Find posts by matching a string of words against their content,
     * optionally making sure they are visible to a certain user.
     *
     * @param  string  $string
     * @param  \Flarum\Core\Models\User|null  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByContent($string, User $user = null);

    /**
     * Get the position within a discussion where a post with a certain number
     * is. If the post with that number does not exist, the index of the
     * closest post to it will be returned.
     *
     * @param  integer  $discussionId
     * @param  integer  $number
     * @param  \Flarum\Core\Models\User|null  $user
     * @return integer
     */
    public function getIndexForNumber($discussionId, $number, User $user = null);
}
