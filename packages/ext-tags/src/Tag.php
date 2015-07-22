<?php namespace Flarum\Tags;

use Flarum\Core\Model;
use Flarum\Core\Discussions\Discussion;

class Tag extends Model
{
    protected $table = 'tags';

    protected $dates = ['last_time'];

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
