<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

interface ExtenderInterface
{
    public function extend(Container $container);
}
