<?php namespace Flarum\Core\Models;

use Tobscure\Permissible\Permissible;
use Flarum\Core\Events\PostWasDeleted;

class Post extends Model
{
    use Permissible;

    /**
     * The validation rules for this model.
     *
     * @var array
     */
    public static $rules = [
        'discussion_id' => 'required|integer',
        'time'          => 'required|date',
        'content'       => 'required',
        'number'        => 'integer',
        'user_id'       => 'integer',
        'edit_time'     => 'date',
        'edit_user_id'  => 'integer',
        'hide_time'     => 'date',
        'hide_user_id'  => 'integer',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['time', 'edit_time', 'hide_time'];

    /**
     * A map of post types, as specified in the `type` column, to their
     * classes.
     *
     * @var array
     */
    protected static $types = [];

    /**
     * Raise an event when a post is deleted. Add an event listener to set the
     * post's number, and update the discussion's number index, when inserting
     * a post.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->type = $post::$type;
            $post->number = ++$post->discussion->number_index;
            $post->discussion->save();
        });

        static::deleted(function ($post) {
            $post->raise(new PostWasDeleted($post));
        });

        static::addGlobalScope(new RegisteredTypesScope);
    }

    /**
     * Define the relationship with the post's discussion.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discussion()
    {
        return $this->belongsTo('Flarum\Core\Models\Discussion', 'discussion_id');
    }

    /**
     * Define the relationship with the post's author.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Models\User', 'user_id');
    }

    /**
     * Define the relationship with the user who edited the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function editUser()
    {
        return $this->belongsTo('Flarum\Core\Models\User', 'edit_user_id');
    }

    /**
     * Define the relationship with the user who hid the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hideUser()
    {
        return $this->belongsTo('Flarum\Core\Models\User', 'hide_user_id');
    }

    /**
     * Terminate the query and return an array of matching IDs.
     * Example usage: `$ids = $discussion->posts()->ids()`
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    public function scopeIds($query)
    {
        return array_map('intval', $query->get(['id'])->fetch('id')->all());
    }

    /**
     * Get all posts, regardless of their type, by removing the
     * `RegisteredTypesScope` global scope constraints applied on this model.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAllTypes($query)
    {
        return $this->removeGlobalScopes($query);
    }

    /**
     * Create a new model instance according to the post's type.
     *
     * @param  array  $attributes
     * @return static|object
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        if (!empty($attributes->type)) {
            $type = $attributes->type;
            if (isset(static::$types[$type])) {
                $class = static::$types[$type];
                if (class_exists($class)) {
                    $instance = new $class;
                    $instance->exists = true;
                    $instance->setRawAttributes((array) $attributes, true);
                    $instance->setConnection($connection ?: $this->connection);
                    return $instance;
                }
            }
        }

        return parent::newFromBuilder($attributes, $connection);
    }

    /**
     * Register a post type and its model class.
     *
     * @param string $class
     * @return void
     */
    public static function addType($class)
    {
        static::$types[$class::$type] = $class;
    }

    public static function getTypes()
    {
        return static::$types;
    }
}
