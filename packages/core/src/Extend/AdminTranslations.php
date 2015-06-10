<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

class AdminTranslations implements ExtenderInterface
{
    protected $keys;

    public function __construct($keys)
    {
        $this->keys = $keys;
    }

    public function extend(Container $container)
    {

    }
}
