<?php namespace Flarum\Extend;

use Illuminate\Foundation\Application;

interface ExtenderInterface
{
    public function extend(Application $app);
}
