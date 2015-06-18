<?php namespace Flarum\Tags;

use Flarum\Core\Models\Model;
use Flarum\Core\Support\Locked;
use Flarum\Core\Support\VisibleScope;

class Tag extends Model
{
    use Locked;
    use VisibleScope;

    protected $table = 'tags';

    protected $dates = ['last_time'];

    public function parent()
    {
        return $this->belongsTo('Flarum\Tags\Tag', 'parent_id');
    }

    public function lastDiscussion()
    {
        return $this->belongsTo('Flarum\Core\Models\Discussion', 'last_discussion_id');
    }

    public static function getVisibleTo($user)
    {
        static $tags;
        if (!$tags) {
            $tags = static::all();
        }

        $ids = [];
        foreach ($tags as $tag) {
            if (! $tag->is_restricted || $user->hasPermission('tag'.$tag->id.'.view')) {
                $ids[] = $tag->id;
            }
        }

        return $ids;
    }
}
