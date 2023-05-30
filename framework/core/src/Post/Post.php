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
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Expression;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

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
 * @property \Flarum\Discussion\Discussion|null $discussion
 * @property User|null $user
 * @property User|null $editedUser
 * @property User|null $hiddenUser
 * @property string $ip_address
 * @property bool $is_private
 */
class Post extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;
    use HasEagerLimit;

    protected $table = 'posts';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'edited_at', 'hidden_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_private' => 'boolean'
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
                    // IFNULL only works on MySQL/MariaDB
                    ->selectRaw('IFNULL(MAX('.$db->getTablePrefix().'pn.number), 0) + 1')
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

    /**
     * Determine whether this post is visible to the given user.
     */
    public function isVisibleTo(User $user): bool
    {
        return (bool) $this->newQuery()->whereVisibleTo($user)->find($this->id);
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
