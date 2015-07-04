<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;
use Flarum\Core\Posts\Post;

class PostType implements ExtenderInterface
{
    protected $class;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function extend(Container $container)
    {
        $class = $this->class;

        Post::setModel($class::$type, $class);
    }
}
