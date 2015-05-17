<?php namespace Flarum\Extend;

use Illuminate\Foundation\Application;
use Flarum\Core\Models\Post;

class PostType implements ExtenderInterface
{
    protected $class;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function extend(Application $app)
    {
        Post::addType($this->class);
    }
}
