<?php namespace Flarum\Core\Models;

use Tobscure\Permissible\Permissible;
use Flarum\Core\Support\EventGenerator;
use Flarum\Core\Events\DiscussionWasDeleted;
use Flarum\Core\Events\DiscussionWasStarted;
use Flarum\Core\Events\DiscussionWasRenamed;
use Flarum\Core\Models\User;

class Discussion extends Model
{
    use Permissible;

    /**
     * The validation rules for this model.
     *
     * @var array
     */
    public static $rules = [
        'title'            => 'required',
        'start_time'       => 'required|date',
        'comments_count'   => 'integer',
        'start_user_id'    => 'integer',
        'start_post_id'    => 'integer',
        'last_time'        => 'date',
        'last_user_id'     => 'integer',
        'last_post_id'     => 'integer',
        'last_post_number' => 'integer'
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
    protected $dates = ['start_time', 'last_time'];

    /**
     * An array of posts that have been added during this request.
     *
     * @var \Flarum\Core\Models\Post[]
     */
    public $addedPosts = [];

    /**
     * The user for which the state relationship should be loaded.
     *
     * @var \Flarum\Core\Models\User
     */
    protected static $stateUser;

    /**
     * Raise an event when a discussion is deleted.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleted(function ($discussion) {
            $discussion->raise(new DiscussionWasDeleted($discussion));

            $discussion->posts()->allTypes()->delete();
            $discussion->readers()->detach();
        });
    }

    /**
     * Create a new instance.
     *
     * @param  string  $title
     * @param  \Flarum\Core\Models\User  $user
     * @return static
     */
    public static function start($title, $user)
    {
        $discussion = new static;

        $discussion->title         = $title;
        $discussion->start_time    = time();
        $discussion->start_user_id = $user->id;

        $discussion->raise(new DiscussionWasStarted($discussion));

        return $discussion;
    }

    /**
     * Rename the discussion.
     *
     * @param  string $title
     * @param  \Flarum\Core\Models\User  $user
     * @return $this
     */
    public function rename($title, $user)
    {
        if ($this->title !== $title) {
            $oldTitle = $this->title;
            $this->title = $title;

            $this->raise(new DiscussionWasRenamed($this, $user, $oldTitle));
        }

        return $this;
    }

    /**
     * Set the discussion's last post details.
     *
     * @param  \Flarum\Core\Models\Post  $post
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
        if ($lastPost = $this->comments()->orderBy('time', 'desc')->first()) {
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
     * Specify that a post was added to this discussion during this request
     * for later retrieval.
     *
     * @param  \Flarum\Core\Models\Post  $post
     * @return void
     */
    public function postWasAdded(Post $post)
    {
        $this->addedPosts[] = $post;
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
     * Define the relationship with the discussion's comments.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->posts()->where('type', 'comment')->whereNull('hide_time');
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
     * Define the relationship with the discussion's last post's author.
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
     * Define the relationship with the discussion's state for a particular user.
     *
     * @param  \Flarum\Core\Models\User  $user
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function state(User $user = null)
    {
        $user = $user ?: static::$stateUser;

        return $this->hasOne('Flarum\Core\Models\DiscussionState')->where('user_id', $user->id);
    }

    /**
     * Get the state model for a user, or instantiate a new one if it does not
     * exist.
     *
     * @param  \Flarum\Core\Models\User  $user
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
     * @param  \Flarum\Core\Models\User  $user
     */
    public static function setStateUser(User $user)
    {
        static::$stateUser = $user;
    }
}
