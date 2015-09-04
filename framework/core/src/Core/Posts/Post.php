<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Posts;

use DomainException;
use Flarum\Events\PostWasDeleted;
use Flarum\Core\Model;
use Flarum\Core\Users\User;
use Flarum\Core\Support\Locked;
use Flarum\Core\Support\VisibleScope;
use Flarum\Core\Support\EventGenerator;
use Flarum\Core\Support\ValidatesBeforeSave;
use Illuminate\Database\Eloquent\Builder;

/**
 * @todo document database columns with @property
 */
class Post extends Model
{
    use EventGenerator;
    use Locked;
    use VisibleScope;
    use ValidatesBeforeSave;

    /**
     * {@inheritdoc}
     */
    protected $table = 'posts';

    /**
     * The validation rules for this model.
     *
     * @var array
     */
    protected $rules = [
        'discussion_id' => 'required|integer',
        'time'          => 'required|date',
        'content'       => 'required|max:65535',
        'number'        => 'integer',
        'user_id'       => 'integer',
        'edit_time'     => 'date',
        'edit_user_id'  => 'integer',
        'hide_time'     => 'date',
        'hide_user_id'  => 'integer',
    ];

    /**
     * {@inheritdoc}
     */
    protected $dates = ['time', 'edit_time', 'hide_time'];

    /**
     * A map of post types, as specified in the `type` column, to their
     * classes.
     *
     * @var array
     */
    protected static $models = [];

    /**
     * The type of post this is, to be stored in the posts table.
     *
     * Should be overwritten by subclasses with the value that is
     * to be stored in the database, which will then be used for
     * mapping the hydrated model instance to the proper subtype.
     *
     * @var string
     */
    public static $type = '';

    /**
     * {@inheritdoc}
     */
    public static function boot()
    {
        parent::boot();

        // When a post is created, set its type according to the value of the
        // subclass. Also give it an auto-incrementing number within the
        // discussion.
        static::creating(function (Post $post) {
            $post->type = $post::$type;
            $post->number = ++$post->discussion->number_index;
            $post->discussion->save();
        });

        // Don't allow the first post in a discussion to be deleted, because
        // it doesn't make sense. The discussion must be deleted instead.
        static::deleting(function (Post $post) {
            if ($post->number == 1) {
                throw new DomainException('Cannot delete the first post of a discussion');
            }
        });

        static::deleted(function (Post $post) {
            $post->raise(new PostWasDeleted($post));
        });

        static::addGlobalScope(new RegisteredTypesScope);
    }

    /**
     * Determine whether or not this post is visible to the given user.
     *
     * @param User $user
     * @return boolean
     */
    public function isVisibleTo(User $user)
    {
        $discussion = $this->discussion()->whereVisibleTo($user)->first();

        if ($discussion) {
            $this->setRelation('discussion', $discussion);

            return (bool) $discussion->postsVisibleTo($user)->where('id', $this->id)->count();
        }

        return false;
    }

    /**
     * Define the relationship with the post's discussion.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discussion()
    {
        return $this->belongsTo('Flarum\Core\Discussions\Discussion', 'discussion_id');
    }

    /**
     * Define the relationship with the post's author.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'user_id');
    }

    /**
     * Define the relationship with the user who edited the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function editUser()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'edit_user_id');
    }

    /**
     * Define the relationship with the user who hid the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hideUser()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'hide_user_id');
    }

    /**
     * Get all posts, regardless of their type, by removing the
     * `RegisteredTypesScope` global scope constraints applied on this model.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeAllTypes(Builder $query)
    {
        return $this->removeGlobalScopes($query);
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

    /**
     * Get the type-to-model map.
     *
     * @return array
     */
    public static function getModels()
    {
        return static::$models;
    }

    /**
     * Set the model for the given post type.
     *
     * @param string $type The post type.
     * @param string $model The class name of the model for that type.
     * @return void
     */
    public static function setModel($type, $model)
    {
        static::$models[$type] = $model;
    }
}
