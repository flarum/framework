<?php namespace Flarum\Tags;

use Flarum\Core\Model;
use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Groups\Permission;
use Flarum\Core\Support\VisibleScope;
use Flarum\Core\Support\Locked;
use Flarum\Core\Support\ValidatesBeforeSave;

class Tag extends Model
{
    use ValidatesBeforeSave;
    use VisibleScope;
    use Locked;

    protected $table = 'tags';

    protected $dates = ['last_time'];

    protected $rules = [
        'name' => 'required',
        'slug' => 'required'
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleted(function ($tag) {
            $tag->discussions()->detach();

            Permission::where('permission', 'like', "tag{$tag->id}.%")->delete();
        });
    }

    /**
     * Create a new tag.
     *
     * @param string $name
     * @param string $slug
     * @param string $description
     * @param string $color
     * @param bool $isHidden
     * @return static
     */
    public static function build($name, $slug, $description, $color, $isHidden)
    {
        $tag = new static;

        $tag->name        = $name;
        $tag->slug        = $slug;
        $tag->description = $description;
        $tag->color       = $color;
        $tag->is_hidden   = (bool) $isHidden;

        return $tag;
    }

    public function parent()
    {
        return $this->belongsTo('Flarum\Tags\Tag', 'parent_id');
    }

    public function lastDiscussion()
    {
        return $this->belongsTo('Flarum\Core\Discussions\Discussion', 'last_discussion_id');
    }

    public function discussions()
    {
        return $this->belongsToMany('Flarum\Core\Discussions\Discussion', 'discussions_tags');
    }

    /**
     * Refresh a tag's last discussion details.
     *
     * @return $this
     */
    public function refreshLastDiscussion()
    {
        if ($lastDiscussion = $this->discussions()->latest('last_time')->first()) {
            $this->setLastDiscussion($lastDiscussion);
        }

        return $this;
    }

    /**
     * Set the tag's last discussion details.
     *
     * @param Discussion $discussion
     * @return $this
     */
    public function setLastDiscussion(Discussion $discussion)
    {
        $this->last_time          = $discussion->last_time;
        $this->last_discussion_id = $discussion->id;

        return $this;
    }

    public static function getNotVisibleTo($user)
    {
        static $tags;

        if (! $tags) {
            $tags = static::all();
        }

        $ids = [];

        foreach ($tags as $tag) {
            if ($tag->is_restricted && ! $user->hasPermission('tag' . $tag->id . '.view')) {
                $ids[] = $tag->id;
            }
        }

        return $ids;
    }
}
