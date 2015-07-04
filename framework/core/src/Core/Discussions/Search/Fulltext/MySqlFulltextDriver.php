<?php namespace Flarum\Core\Discussions\Search\Fulltext;

use Flarum\Core\Posts\Post;

class MySqlFulltextDriver implements DriverInterface
{
    public function match($string)
    {
        return Post::whereRaw('MATCH (`content`) AGAINST (? IN BOOLEAN MODE)', [$string])
            ->orderByRaw('MATCH (`content`) AGAINST (?) DESC', [$string])
            ->lists('id');
    }
}
