<?php namespace Flarum\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\User;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Search\Discussions\Fulltext\DriverInterface;

// TODO: In some cases, the use of a post repository incurs extra query expense,
// because for every post retrieved we need to check if the discussion it's in
// is visible. Especially when retrieving a discussion's posts, we can end up
// with an inefficient chain of queries like this:
// 1. Api\Discussions\ShowAction: get discussion (will exit if not visible)
// 2. Discussion@visiblePosts: get discussion tags (for post visibility purposes)
// 3. Discussion@visiblePosts: get post IDs
// 4. EloquentPostRepository@getIndexForNumber: get discussion
// 5. EloquentPostRepository@getIndexForNumber: get discussion tags (for post visibility purposes)
// 6. EloquentPostRepository@getIndexForNumber: get post index for number
// 7. EloquentPostRepository@findWhere: get post IDs for discussion to check for discussion visibility
// 8. EloquentPostRepository@findWhere: get post IDs in visible discussions
// 9. EloquentPostRepository@findWhere: get posts
// 10. EloquentPostRepository@findWhere: eager load discussion onto posts
// 11. EloquentPostRepository@findWhere: get discussion tags to filter visible posts
// 12. Api\Discussions\ShowAction: eager load users
// 13. Api\Discussions\ShowAction: eager load groups
// 14. Api\Discussions\ShowAction: eager load mentions
// 14. Serializers\DiscussionSerializer: load discussion-user state

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
        $posts = $this->findByIds([$id], $user);

        if (! count($posts)) {
            throw new ModelNotFoundException;
        }

        return $posts->first();
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

        $ids = $query->lists('id');

        return $this->findByIds($ids, $user);
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
        $ids = $this->filterDiscussionVisibleTo($ids, $user);

        $posts = Post::with('discussion')->whereIn('id', (array) $ids)->get();

        return $this->filterVisibleTo($posts, $user);
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

        $ids = $this->filterDiscussionVisibleTo($ids, $user);

        $query = Post::select('id', 'discussion_id')->whereIn('id', $ids);

        foreach ($ids as $id) {
            $query->orderByRaw('id != ?', [$id]);
        }

        $posts = $query->get();

        return $this->filterVisibleTo($posts, $user);
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
        $query = Discussion::find($discussionId)
            ->visiblePosts($user)
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

    protected function filterDiscussionVisibleTo($ids, $user)
    {
        // For each post ID, we need to make sure that the discussion it's in
        // is visible to the user.
        if ($user) {
            $ids = Discussion::join('posts', 'discussions.id', '=', 'posts.discussion_id')
                ->whereIn('posts.id', $ids)
                ->whereVisibleTo($user)
                ->get(['posts.id'])
                ->lists('id');
        }

        return $ids;
    }

    protected function filterVisibleTo($posts, $user)
    {
        if ($user) {
            $posts = $posts->filter(function ($post) use ($user) {
                return $post->can($user, 'view');
            });
        }

        return $posts;
    }
}
