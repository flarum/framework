<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\Notification\Notification;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\DeletingPosts;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;

/**
 * @property int $id
 * @property int $discussion_id
 * @property int|Expression $number
 * @property \Carbon\Carbon $created_at
 * @property int|null $user_id
 * @property string|null $type
 * @property string|null $content
 * @property \Carbon\Carbon|null $edited_at
 * @property int|null $edited_user_id
 * @property \Carbon\Carbon|null $hidden_at
 * @property int|null $hidden_user_id
 * @property string $ip_address
 * @property bool $is_private
 * @property string|null $created_via
 * @property string|null $created_via_name
 * @property-read Discussion|null $discussion
 * @property-read User|null $user
 * @property-read User|null $editedUser
 * @property-read User|null $hiddenUser
 */
class Post extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;
    use HasFactory;

    protected $table = 'posts';

    protected $casts = [
        'id' => 'integer',
        'discussion_id' => 'integer',
        'number' => 'integer',
        'user_id' => 'integer',
        'edited_user_id' => 'integer',
        'hidden_user_id' => 'integer',
        'is_private' => 'boolean',
        'created_at' => 'datetime',
        'edited_at' => 'datetime',
        'hidden_at' => 'datetime'
    ];

    /**
     * A map of post types, as specified in the `type` column, to their
     * classes.
     *
     * @var array<string, class-string<Post>>
     */
    protected static array $models = [];

    /**
     * The type of post this is, to be stored in the posts table.
     *
     * Should be overwritten by subclasses with the value that is
     * to be stored in the database, which will then be used for
     * mapping the hydrated model instance to the proper subtype.
     */
    public static string $type = '';

    /**
     * How many times a write that competes with a like counter is attempted.
     *
     * @see self::conflictAttempts()
     */
    public const CONFLICT_ATTEMPTS = 3;

    /**
     * Whether the current save already holds the discussion lock.
     */
    private bool $discussionLocked = false;

    public static function boot()
    {
        parent::boot();

        // When a post is created, set its type according to the value of the
        // subclass. Also give it an auto-incrementing number within the
        // discussion.
        static::creating(function (self $post) {
            $post->type = $post::$type;

            $db = static::getConnectionResolver()->connection();

            $post->number = new Expression('('.
                $db->table('posts', 'pn')
                    ->whereRaw($db->getTablePrefix().'pn.discussion_id = '.intval($post->discussion_id))
                    ->selectRaw('COALESCE(MAX('.$db->getTablePrefix().'pn.number), 0) + 1')
                    ->toSql()
            .')');
        });

        static::created(function (self $post) {
            $post->refresh();
            $post->discussion->save();
        });

        static::deleted(function (self $post) {
            $post->raise(new Deleted($post));

            Notification::whereSubject($post)->delete();
        });

        static::addGlobalScope(new RegisteredTypesScope);
    }

    public function delete()
    {
        if (! $this->exists) {
            return parent::delete();
        }

        return self::deleteWithEvents(
            $this->newModelQuery()->whereKey($this->getKey()),
            function ($posts) {
                if (! $posts->exists()) {
                    return false;
                }

                // A retry follows an attempt that was rolled back, but Eloquent
                // kept the model marked deleted, so the row would be skipped.
                $this->exists = true;

                return parent::delete();
            },
        );
    }

    public function save(array $options = [])
    {
        // Only visibility changes need the discussion locked. The lock has to
        // be taken before the saving listeners: core's ModelPrivate checkers
        // derive `is_private` from `is_approved` there, and the likes counters
        // are refreshed from `updated`. Ordinary edits take neither the lock
        // nor a transaction.
        if (! $this->exists || ! $this->visibilityIsDirty()) {
            return parent::save($options);
        }

        return $this->withDiscussionLock(function () use ($options) {
            $this->discussionLocked = true;

            try {
                return parent::save($options);
            } finally {
                $this->discussionLocked = false;
            }
        });
    }

    protected function performUpdate(Builder $query)
    {
        // A saving listener may have changed visibility after save() looked.
        // Every saving listener has run by the time Eloquent gets here, so the
        // lock can still be taken before the visibility write and its
        // `updated` listeners, which is what has to be serialized. Taking it
        // later only leaves the saving listeners themselves unlocked. A
        // visibility change made in an `updating` listener is not covered.
        if (! $this->discussionLocked && $this->visibilityIsDirty()) {
            return $this->withDiscussionLock(function () use ($query) {
                $this->discussionLocked = true;

                try {
                    return parent::performUpdate($query);
                } finally {
                    $this->discussionLocked = false;
                }
            });
        }

        return parent::performUpdate($query);
    }

    private function visibilityIsDirty(): bool
    {
        return $this->isDirty('is_private') || $this->isDirty('is_approved');
    }

    /**
     * Run a post mutation with its discussion locked before the post.
     *
     * @template T
     * @param \Closure(): T $callback
     * @return T
     */
    public function withDiscussionLock(\Closure $callback, int $attempts = 1): mixed
    {
        $db = $this->getConnection();

        return $db->transaction(function () use ($db, $callback) {
            $db->table('discussions')->where('id', $this->discussion_id)->lockForUpdate()->first(['id']);

            return $callback();
        }, $attempts);
    }

    /**
     * Attempts for a transaction whose writes can collide with a like counter.
     *
     * A liker can commit their counter update between our reads and our write
     * to that user. MariaDB 11.6+ reports that as a deadlock (1213) or
     * "record has changed since last read" (1020) instead of waiting, so the
     * write has to be retried. Retrying only helps on the outermost
     * transaction: InnoDB rolls the whole transaction back on a deadlock, so a
     * nested retry would repeat a savepoint inside a transaction that is gone.
     */
    public static function conflictAttempts(ConnectionInterface $connection): int
    {
        return $connection->transactionLevel() === 0 ? self::CONFLICT_ATTEMPTS : 1;
    }

    /**
     * @template T
     * @param Builder<Post> $posts A single-table, unpaginated filter on posts.
     * @param \Closure(\Illuminate\Database\Query\Builder): T $delete Delete the posts the given query selects,
     *                               or return false to cancel. It can run more than once when the transaction is retried.
     * @param list<int> $userIds Other users to lock alongside those requested by deletion listeners.
     * @return T
     *
     * Listeners may run once per retry attempt, so they must be idempotent.
     */
    public static function deleteWithEvents(Builder $posts, \Closure $delete, array $userIds = []): mixed
    {
        $query = $posts->toBase();
        if ($query->from !== 'posts' || $query->limit !== null || $query->offset !== null ||
            $query->orders || $query->joins || $query->groups || $query->havings || $query->unions ||
            $query->groupLimit || $query->distinct) {
            throw new \InvalidArgumentException('Post deletion requires an unordered, unpaginated query on posts.');
        }

        $db = $query->getConnection();

        return $db->transaction(function () use ($query, $db, $delete, $userIds) {
            // Lock each discussion the posts belong to before its posts. A post
            // whose discussion row is already gone has no discussion to lock,
            // and is still deleted below rather than being left behind.
            $db->table('discussions')
                ->whereIn('id', (clone $query)->select('posts.discussion_id'))
                ->orderBy('id')->lockForUpdate()->pluck('id');

            // The posts being deleted, as a subquery rather than a list.
            // Listeners and the delete callback both work from it, so a big
            // discussion costs SQL work instead of memory.
            $deleted = (clone $query)->select('posts.id');

            // Lock the posts in bounded chunks, which are thrown away: the
            // listeners get the subquery, not the rows.
            (clone $deleted)->orderBy('posts.id')->lockForUpdate()
                ->chunkById(1000, fn () => null, 'posts.id', 'id');

            $event = new DeletingPosts($db, $deleted, $userIds);
            resolve(Dispatcher::class)->dispatch($event);
            $event->acquireUserLocks();

            $result = $delete(clone $deleted);

            if ($result !== false) {
                $event->complete();
            }

            return $result;
        }, self::conflictAttempts($db));
    }

    /**
     * Determine whether this post is visible to the given user.
     */
    public function isVisibleTo(User $user): bool
    {
        return (bool) $this->newQuery()->whereVisibleTo($user)->find($this->id);
    }

    /**
     * Scope a query over one discussion's posts to those visible to the user,
     * where the caller has already established that the DISCUSSION is visible.
     *
     * Applies the same registered `view` scopers as whereVisibleTo(), but lets
     * scopers implementing {@see Access\ScopesPostVisibilityWithinDiscussion}
     * take their fast path: discussion-level access checks are evaluated once
     * in PHP instead of as per-row subqueries, whose cost grows with the size
     * of the discussion. Only meaningful when the query is restricted to the
     * given discussion's posts.
     */
    public function scopeWhereVisibleToInDiscussion(Builder $query, User $actor, Discussion $discussion): Builder
    {
        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            foreach (Arr::get(static::$visibilityScopers, "$class.*", []) as $scoper) {
                if ($scoper instanceof Access\ScopesPostVisibilityWithinDiscussion) {
                    $scoper->withinDiscussion($actor, $query, $discussion);
                } else {
                    $scoper($actor, $query, 'view');
                }
            }
            foreach (Arr::get(static::$visibilityScopers, "$class.view", []) as $scoper) {
                if ($scoper instanceof Access\ScopesPostVisibilityWithinDiscussion) {
                    $scoper->withinDiscussion($actor, $query, $discussion);
                } else {
                    $scoper($actor, $query);
                }
            }
        }

        return $query;
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_user_id');
    }

    public function hiddenUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_user_id');
    }

    /**
     * Get all posts, regardless of their type, by removing the
     * `RegisteredTypesScope` global scope constraints applied on this model.
     */
    public function scopeAllTypes(Builder $query): Builder
    {
        return $query->withoutGlobalScopes();
    }

    /**
     * Create a new model instance according to the post's type.
     *
     * @param array $attributes
     * @param string|null $connection
     * @return static|object
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $attributes = (array) $attributes;

        if (! empty($attributes['type'])
            && isset(static::$models[$attributes['type']])
            && class_exists($class = static::$models[$attributes['type']])
        ) {
            /** @var Post $instance */
            $instance = new $class;
            $instance->exists = true;
            $instance->setRawAttributes($attributes, true);
            $instance->setConnection($connection ?: $this->connection);

            return $instance;
        }

        return parent::newFromBuilder($attributes, $connection);
    }

    public static function getModels(): array
    {
        return static::$models;
    }

    /**
     * @internal
     */
    public static function setModel(string $type, string $model): void
    {
        static::$models[$type] = $model;
    }
}
