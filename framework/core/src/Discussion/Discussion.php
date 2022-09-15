<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Discussion\Event\Deleted;
use Flarum\Discussion\Event\Hidden;
use Flarum\Discussion\Event\Renamed;
use Flarum\Discussion\Event\Restored;
use Flarum\Discussion\Event\Started;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\Notification\Notification;
use Flarum\Post\MergeableInterface;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int $comment_count
 * @property int $participant_count
 * @property int $post_number_index !!DEPRECATED!!
 * @property \Carbon\Carbon $created_at
 * @property int|null $user_id
 * @property int|null $first_post_id
 * @property \Carbon\Carbon|null $last_posted_at
 * @property int|null $last_posted_user_id
 * @property int|null $last_post_id
 * @property int|null $last_post_number
 * @property \Carbon\Carbon|null $hidden_at
 * @property int|null $hidden_user_id
 * @property UserState|null $state
 * @property \Illuminate\Database\Eloquent\Collection $posts
 * @property \Illuminate\Database\Eloquent\Collection $comments
 * @property \Illuminate\Database\Eloquent\Collection $participants
 * @property Post|null $firstPost
 * @property User|null $user
 * @property Post|null $lastPost
 * @property User|null $lastPostedUser
 * @property \Illuminate\Database\Eloquent\Collection $readers
 * @property bool $is_private
 */
class Discussion extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;

    /**
     * An array of posts that have been modified during this request.
     *
     * @var array
     */
    protected $modifiedPosts = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'last_posted_at', 'hidden_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_private' => 'boolean'
    ];

    /**
     * The user for which the state relationship should be loaded.
     *
     * @var User|null
     */
    protected static $stateUser;

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleting(function (self $discussion) {
            Notification::whereSubjectModel(Post::class)
                ->whereIn('subject_id', function ($query) use ($discussion) {
                    $query->select('id')->from('posts')->where('discussion_id', $discussion->id);
                })
                ->delete();
        });

        static::deleted(function (self $discussion) {
            $discussion->raise(new Deleted($discussion));

            Notification::whereSubject($discussion)->delete();
        });
    }

    /**
     * Start a new discussion. Raises the DiscussionWasStarted event.
     *
     * @param string $title
     * @param User $user
     * @return static
     */
    public static function start($title, User $user)
    {
        $discussion = new static;

        $discussion->title = $title;
        $discussion->created_at = Carbon::now();
        $discussion->user_id = $user->id;

        $discussion->setRelation('user', $user);

        $discussion->raise(new Started($discussion));

        return $discussion;
    }

    /**
     * Rename the discussion. Raises the DiscussionWasRenamed event.
     *
     * @param string $title
     * @return $this
     */
    public function rename($title)
    {
        if ($this->title !== $title) {
            $oldTitle = $this->title;
            $this->title = $title;

            $this->raise(new Renamed($this, $oldTitle));
        }

        return $this;
    }

    /**
     * Hide the discussion.
     *
     * @param User $actor
     * @return $this
     */
    public function hide(User $actor = null)
    {
        if (! $this->hidden_at) {
            $this->hidden_at = Carbon::now();
            $this->hidden_user_id = $actor ? $actor->id : null;

            $this->raise(new Hidden($this));
        }

        return $this;
    }

    /**
     * Restore the discussion.
     *
     * @return $this
     */
    public function restore()
    {
        if ($this->hidden_at !== null) {
            $this->hidden_at = null;
            $this->hidden_user_id = null;

            $this->raise(new Restored($this));
        }

        return $this;
    }

    /**
     * Set the discussion's first post details.
     *
     * @param Post $post
     * @return $this
     */
    public function setFirstPost(Post $post)
    {
        $this->created_at = $post->created_at;
        $this->user_id = $post->user_id;
        $this->first_post_id = $post->id;

        return $this;
    }

    /**
     * Set the discussion's last post details.
     *
     * @param Post $post
     * @return $this
     */
    public function setLastPost(Post $post)
    {
        $this->last_posted_at = $post->created_at;
        $this->last_posted_user_id = $post->user_id;
        $this->last_post_id = $post->id;
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
        if ($lastPost = $this->comments()->latest()->first()) {
            /** @var Post $lastPost */
            $this->setLastPost($lastPost);
        }

        return $this;
    }

    /**
     * Refresh the discussion's comment count.
     *
     * @return $this
     */
    public function refreshCommentCount()
    {
        $this->comment_count = $this->comments()->count();

        return $this;
    }

    /**
     * Refresh the discussion's participant count.
     *
     * @return $this
     */
    public function refreshParticipantCount()
    {
        $this->participant_count = $this->participants()->count('users.id');

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
     * @template T of \Flarum\Post\MergeableInterface
     * @param T $post The post to save.
     * @return T The resulting post. It may or may not be the same post as
     *     was originally intended to be saved. It also may not exist, if the
     *     merge logic resulted in deletion.
     */
    public function mergePost(MergeableInterface $post)
    {
        $lastPost = $this->posts()->latest()->first();

        $post = $post->saveAfter($lastPost);

        return $this->modifiedPosts[] = $post;
    }

    /**
     * Get the posts that have been modified during this request.
     *
     * @return array
     */
    public function getModifiedPosts()
    {
        return $this->modifiedPosts;
    }

    /**
     * Define the relationship with the discussion's posts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Define the relationship with the discussion's publicly-visible comments.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Post>
     */
    public function comments()
    {
        return $this->posts()
            ->where('is_private', false)
            ->whereNull('hidden_at')
            ->where('type', 'comment');
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
            ->where('posts.is_private', false)
            ->where('posts.type', 'comment')
            ->select('users.*')
            ->distinct();
    }

    /**
     * Define the relationship with the discussion's first post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function firstPost()
    {
        return $this->belongsTo(Post::class, 'first_post_id');
    }

    /**
     * Define the relationship with the discussion's author.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Define the relationship with the discussion's last post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastPost()
    {
        return $this->belongsTo(Post::class, 'last_post_id');
    }

    /**
     * Define the relationship with the discussion's most recent author.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lastPostedUser()
    {
        return $this->belongsTo(User::class, 'last_posted_user_id');
    }

    /**
     * Define the relationship with the discussion's most relevant post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mostRelevantPost()
    {
        return $this->belongsTo(Post::class, 'most_relevant_post_id');
    }

    /**
     * Define the relationship with the discussion's readers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function readers()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Define the relationship with the discussion's state for a particular
     * user.
     *
     * If no user is passed (i.e. in the case of eager loading the 'state'
     * relation), then the static `$stateUser` property is used.
     *
     * @param User|null $user
     * @return HasOne
     *
     * @see Discussion::setStateUser()
     */
    public function state(User $user = null): HasOne
    {
        $user = $user ?: static::$stateUser;

        return $this->hasOne(UserState::class)->where('user_id', $user ? $user->id : null);
    }

    /**
     * Get the state model for a user, or instantiate a new one if it does not
     * exist.
     */
    public function stateFor(User $user): UserState
    {
        /** @var UserState|null $state */
        $state = $this->state($user)->first();

        if (! $state) {
            $state = new UserState;
            $state->discussion_id = $this->id;
            $state->user_id = $user->id;
        }

        return $state;
    }

    /**
     * Set the user for which the state relationship should be loaded.
     */
    public static function setStateUser(User $user)
    {
        static::$stateUser = $user;
    }

    /**
     * Set the discussion title.
     *
     * This automatically creates a matching slug for the discussion.
     */
    protected function setTitleAttribute(string $title)
    {
        $this->attributes['title'] = $title;
        $this->slug = Str::slug(
            $title,
            '-',
            resolve(SettingsRepositoryInterface::class)->get('default_locale', 'en')
        );
    }
}
