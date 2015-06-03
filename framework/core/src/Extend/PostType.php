<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;
use Flarum\Core\Models\Post;

class PostType implements ExtenderInterface
{
    protected $class;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function extend(Container $container)
    {
        Post::addType($this->class);
    }
}
