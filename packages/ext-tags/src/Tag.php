<?php namespace Flarum\Tags;

use Flarum\Core\Models\Model;

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
        return $this->belongsTo('Flarum\Core\Models\Discussion', 'last_discussion_id');
    }
}
