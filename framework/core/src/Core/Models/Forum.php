<?php namespace Flarum\Core\Models;

use Tobscure\Permissible\Permissible;
use Flarum\Core;

class Forum extends Model
{
    use Permissible;

    public function getTitleAttribute()
    {
        return Core::config('forum_title');
    }
}
