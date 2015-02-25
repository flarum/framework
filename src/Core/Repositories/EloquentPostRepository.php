<?php namespace Flarum\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\User;

class EloquentPostRepository implements PostRepositoryInterface
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
    public function findOrFail($id, User $user = null)
    {
        $query = Post::where('id', $id);

        return $this->scopeVisibleForUser($query, $user)->firstOrFail();
    }

    /**
     * Find posts in a discussion, optionally making sure they are visible to
     * a certain user, and/or using other criteria.
     *
     * @param  integer  $discussionId
     * @param  \Flarum\Core\Models\User|null  $user
     * @param  string  $sort
     * @param  string  $order
     * @param  integer  $count
     * @param  integer  $start
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByDiscussion($discussionId, User $user = null, $sort = 'time', $order = 'asc', $count = null, $start = 0)
    {
        $query = Post::where('discussion_id', $discussionId)
            ->orderBy($sort, $order)
            ->skip($start)
            ->take($count);

        return $this->scopeVisibleForUser($query, $user)->get();
    }

    /**
     * Find posts by their IDs, optionally making sure they are visible to a
     * certain user.
     *
     * @param  array  $ids
     * @param  \Flarum\Core\Models\User|null  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByIds(array $ids, User $user = null)
    {
        $query = Post::whereIn('id', (array) $ids);

        return $this->scopeVisibleForUser($query, $user)->get();
    }

    /**
     * Find posts by matching a string of words against their content,
     * optionally making sure they are visible to a certain user.
     *
     * @param  string  $string
     * @param  \Flarum\Core\Models\User|null  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByContent($string, User $user = null)
    {
        $query = Post::select('id', 'discussion_id')
            ->where('content', 'like', '%'.$string.'%');
            // ->whereRaw('MATCH (`content`) AGAINST (? IN BOOLEAN MODE)', [$string])
            // ->orderByRaw('MATCH (`content`) AGAINST (?) DESC', [$string])

        return $this->scopeVisibleForUser($query, $user)->get();
    }

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
    public function getIndexForNumber($discussionId, $number, User $user = null)
    {
        $query = Post::where('discussion_id', $discussionId)
            ->where('time', '<', function ($query) use ($discussionId, $number) {
                $query->select('time')
                      ->from('posts')
                      ->where('discussion_id', $discussionId)
                      ->whereNotNull('number')
                      ->take(1)

                      // We don't add $number as a binding because for some
                      // reason doing so makes the bindings go out of order.
                      ->orderByRaw('ABS(CAST(number AS SIGNED) - '.(int) $number.')')
            });

        return $this->scopeVisibleForUser($query, $user)->count();
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Flarum\Core\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function scopeVisibleForUser(Builder $query, User $user = null)
    {
        if ($user !== null) {
            $query->whereCan($user, 'view');
        }

        return $query;
    }
}
