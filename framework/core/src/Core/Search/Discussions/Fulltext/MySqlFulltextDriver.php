<?php namespace Flarum\Core\Search\Discussions\Fulltext;

use Flarum\Core\Models\Post;

class MySqlFulltextDriver implements DriverInterface
{
    public function match($string)
    {
        return Post::whereRaw('MATCH (`content`) AGAINST (? IN BOOLEAN MODE)', [$string])
            ->orderByRaw('MATCH (`content`) AGAINST (?) DESC', [$string])
            ->lists('id');
    }
}
