<?php namespace Flarum\Core\Models;

use Flarum\Core\Support\Locked;
use Flarum\Core;

class Forum extends Model
{
    use Locked;

    protected static $relationships = [];

    public function getTitleAttribute()
    {
        return Core::config('forum_title');
    }
}
