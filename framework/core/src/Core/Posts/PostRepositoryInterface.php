<?php namespace Flarum\Core\Posts;

use Flarum\Core\Users\User;

interface PostRepositoryInterface
{
    /**
     * Find a post by ID, optionally making sure it is visible to a certain
     * user, or throw an exception.
     *
     * @param integer $id
     * @param \Flarum\Core\Users\User $actor
     * @return \Flarum\Core\Posts\Post
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, User $actor = null);

    /**
     * Find posts that match certain conditions, optionally making sure they
     * are visible to a certain user, and/or using other criteria.
     *
     * @param array $where
     * @param \Flarum\Core\Users\User|null $actor
     * @param array $sort
     * @param integer $count
     * @param integer $start
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhere($where = [], User $actor = null, $sort = [], $count = null, $start = 0);

    /**
     * Find posts by their IDs, optionally making sure they are visible to a
     * certain user.
     *
     * @param array $ids
     * @param \Flarum\Core\Users\User|null $actor
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByIds(array $ids, User $actor = null);

    /**
     * Find posts by matching a string of words against their content,
     * optionally making sure they are visible to a certain user.
     *
     * @param string $string
     * @param \Flarum\Core\Users\User|null $actor
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByContent($string, User $actor = null);

    /**
     * Get the position within a discussion where a post with a certain number
     * is. If the post with that number does not exist, the index of the
     * closest post to it will be returned.
     *
     * @param integer $discussionId
     * @param integer $number
     * @param \Flarum\Core\Users\User|null $actor
     * @return integer
     */
    public function getIndexForNumber($discussionId, $number, User $actor = null);
}
