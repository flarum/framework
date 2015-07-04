<?php namespace Flarum\Core;

use Flarum\Core\Support\Locked;
use Flarum\Core;

class Forum extends Model
{
    use Locked;

    public function getTitleAttribute()
    {
        return Core::config('forum_title');
    }
}
