<?php namespace Flarum\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\User;
use Flarum\Core\Search\Discussions\Fulltext\DriverInterface;

class EloquentPostRepository implements PostRepositoryInterface
{
    protected $fulltext;

    public function __construct(DriverInterface $fulltext)
    {
        $this->fulltext = $fulltext;
    }

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
     * Find posts that match certain conditions, optionally making sure they
     * are visible to a certain user, and/or using other criteria.
     *
     * @param  array  $where
     * @param  \Flarum\Core\Models\User|null  $user
     * @param  array  $sort
     * @param  integer  $count
     * @param  integer  $start
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findWhere($where = [], User $user = null, $sort = [], $count = null, $start = 0)
    {
        $query = Post::where($where)
            ->skip($start)
            ->take($count);

        foreach ((array) $sort as $field => $order) {
            $query->orderBy($field, $order);
        }

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
        $ids = $this->fulltext->match($string);

        $query = Post::select('id', 'discussion_id')->whereIn('id', $ids);

        foreach ($ids as $id) {
            $query->orderByRaw('id != ?', [$id]);
        }

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
                      ->orderByRaw('ABS(CAST(number AS SIGNED) - '.(int) $number.')');
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
