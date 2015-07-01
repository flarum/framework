<?php namespace Flarum\Core\Models;

use Flarum\Core\Support\EventGenerator;
use Flarum\Core\Support\Locked;
use Flarum\Core\Support\VisibleScope;
use Flarum\Core\Events\DiscussionWasDeleted;
use Flarum\Core\Events\DiscussionWasStarted;
use Flarum\Core\Events\DiscussionWasRenamed;
use Flarum\Core\Events\PostWasDeleted;
use Flarum\Core\Models\User;

class Discussion extends Model
{
    use EventGenerator;
    use Locked;
    use VisibleScope;

    /**
     * The validation rules for this model.
     *
     * @var array
     */
    public static $rules = [
        'title'              => 'required',
        'start_time'         => 'required|date',
        'comments_count'     => 'integer',
        'participants_count' => 'integer',
        'start_user_id'      => 'integer',
        'start_post_id'      => 'integer',
        'last_time'          => 'date',
        'last_user_id'       => 'integer',
        'last_post_id'       => 'integer',
        'last_post_number'   => 'integer'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'discussions';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected static $dateAttributes = ['start_time', 'last_time'];

    /**
     * The user for which the state relationship should be loaded.
     *
     * @var \Flarum\Core\Models\User
     */
    protected static $stateUser;

    /**
     * An array of callables that apply constraints to the visiblePosts query.
     *
     * @var callable[]
     */
    protected static $visiblePostsScopes = [];

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleted(function ($discussion) {
            $discussion->raise(new DiscussionWasDeleted($discussion));

            // Delete all of the posts in the discussion. Before we delete them
            // in a big batch query, we will loop through them and raise a
            // PostWasDeleted event for each post.
            $posts = $discussion->posts()->allTypes();

            foreach ($posts->get() as $post) {
                $discussion->raise(new PostWasDeleted($post));
            }

            $posts->delete();

            // Delete all of the 'state' records for all of the users who have
            // read the discussion.
            $discussion->readers()->detach();
        });
    }

    /**
     * Start a new discussion. Raises the DiscussionWasStarted event.
     *
     * @param string $title
     * @param \Flarum\Core\Models\User $user
     * @return \Flarum\Core\Models\Discussion
     */
    public static function start($title, User $user)
    {
        $discussion = new static;

        $discussion->title         = $title;
        $discussion->start_time    = time();
        $discussion->start_user_id = $user->id;

        $discussion->raise(new DiscussionWasStarted($discussion));

        return $discussion;
    }

    /**
     * Rename the discussion. Raises the DiscussionWasRenamed event.
     *
     * @param string $title
     * @param \Flarum\Core\Models\User $user
     * @return $this
     */
    public function rename($title, User $user)
    {
        if ($this->title !== $title) {
            $oldTitle = $this->title;
            $this->title = $title;

            $this->raise(new DiscussionWasRenamed($this, $user, $oldTitle));
        }

        return $this;
    }

    /**
     * Set the discussion's start post details.
     *
     * @param \Flarum\Core\Models\Post $post
     * @return $this
     */
    public function setStartPost(Post $post)
    {
        $this->start_time    = $post->time;
        $this->start_user_id = $post->user_id;
        $this->start_post_id = $post->id;

        return $this;
    }

    /**
     * Set the discussion's last post details.
     *
     * @param \Flarum\Core\Models\Post $post
     * @return $this
     */
    public function setLastPost(Post $post)
    {
        $this->last_time        = $post->time;
        $this->last_user_id     = $post->user_id;
        $this->last_post_id     = $post->id;
        $this->last_post_number = $post->number;

        return $this;
    }

    /**
     * Refresh a discussion's last post details.
     *
     * @return $this
     */
    public function refreshLastPost()
    {
        if ($lastPost = $this->comments()->latest('time')->first()) {
            $this->setLastPost($lastPost);
        }

        return $this;
    }

    /**
     * Refresh the discussion's comments count.
     *
     * @return $this
     */
    public function refreshCommentsCount()
    {
        $this->comments_count = $this->comments()->count();

        return $this;
    }

    /**
     * Refresh the discussion's participants count.
     *
     * @return $this
     */
    public function refreshParticipantsCount()
    {
        $this->participants_count = $this->participants()->count('users.id');

        return $this;
    }

    /**
     * Save a post, attempting to merge it with the discussion's last post.
     *
     * The merge logic is delegated to the new post. (As an example, a
     * DiscussionRenamedPost will merge if adjacent to another
     * DiscussionRenamedPost, and delete if the title has been reverted
     * completely.)
     *
     * @param \Flarum\Core\Posts\Post $post The post to save.
     * @return \Flarum\Core\Posts\Post The resulting post. It may or may not be
     *     the same post as was originally intended to be saved. It also may not
     *     exist, if the merge logic resulted in deletion.
     */
    public function mergePost(Mergable $post)
    {
        $lastPost = $this->posts()->latest('time')->first();

        return $post->saveAfter($lastPost);
    }

    /**
     * Define the relationship with the discussion's posts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany('Flarum\Core\Models\Post');
    }

    /**
     * Define the relationship with the discussion's posts, but only ones which
     * are visible to the given user.
     *
     * @param \Flarum\Core\Models\User $user
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function visiblePosts(User $user)
    {
        $query = $this->posts();

        foreach (static::$visiblePostsScopes as $scope) {
            $scope($query, $user, $this);
        }

        return $query;
    }

    /**
     * Define the relationship with the discussion's publicly-visible comments.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->visiblePosts(new Guest)->where('type', 'comment');
    }

    /**
     * Query the discussion's participants (a list of unique users who have
     * posted in the discussion).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function participants()
    {
        return User::join('posts', 'posts.user_id', '=', 'users.id')
            ->where('posts.discussion_id', $this->id)
            ->select('users.*')
            ->distinct();
    }

    /**
     * Define the relationship with the discussion's first post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function startPost()
    {
        return $this->belongsTo('Flarum\Core\Models\Post', 'start_post_id');
    }

    /**
     * Define the relationship with the discussion's author.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function startUser()
    {
        return $this->belongsTo('Flarum\Core\Models\User', 'start_user_id');
    }

    /**
     * Define the relationship with the discussion's last post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastPost()
    {
        return $this->belongsTo('Flarum\Core\Models\Post', 'last_post_id');
    }

    /**
     * Define the relationship with the discussion's most recent author.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastUser()
    {
        return $this->belongsTo('Flarum\Core\Models\User', 'last_user_id');
    }

    /**
     * Define the relationship with the discussion's readers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function readers()
    {
        return $this->belongsToMany('Flarum\Core\Models\User', 'users_discussions');
    }

    /**
     * Define the relationship with the discussion's state for a particular
     * user.
     *
     * If no user is passed (i.e. in the case of eager loading the 'state'
     * relation), then the static `$stateUser` property is used.
     *
     * @see \Flarum\Core\Models\Discussion::setStateUser()
     *
     * @param \Flarum\Core\Models\User $user
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function state(User $user = null)
    {
        $user = $user ?: static::$stateUser;

        return $this->hasOne('Flarum\Core\Models\DiscussionState')->where('user_id', $user ? $user->id : null);
    }

    /**
     * Get the state model for a user, or instantiate a new one if it does not
     * exist.
     *
     * @param \Flarum\Core\Models\User $user
     * @return \Flarum\Core\Models\DiscussionState
     */
    public function stateFor(User $user)
    {
        $state = $this->state($user)->first();

        if (! $state) {
            $state = new DiscussionState;
            $state->discussion_id = $this->id;
            $state->user_id = $user->id;
        }

        return $state;
    }

    /**
     * Set the user for which the state relationship should be loaded.
     *
     * @param \Flarum\Core\Models\User $user
     */
    public static function setStateUser(User $user)
    {
        static::$stateUser = $user;
    }

    /**
     * Constrain which posts are visible to a user.
     *
     * @param callable $scope A callback that applies constraints to the posts
     *     query. It is passed three parameters: the query builder object, the
     *     user to constrain posts for, and the discussion instance.
     */
    public static function addVisiblePostsScope(callable $scope)
    {
        static::$visiblePostsScopes[] = $scope;
    }
}
