<?php namespace Flarum\Core\Models;

use Tobscure\Permissible\Permissible;
use Flarum\Core;

class Forum extends Model
{
    use Permissible;

    protected static $relationships = [];

    public function getTitleAttribute()
    {
        return Core::config('forum_title');
    }
}
