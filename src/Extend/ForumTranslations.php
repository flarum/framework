<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;
use Flarum\Forum\Actions\IndexAction;

class ForumTranslations implements ExtenderInterface
{
    protected $keys;

    public function __construct($keys)
    {
        $this->keys = $keys;
    }

    public function extend(Container $container)
    {
        IndexAction::$translations = array_merge(IndexAction::$translations, $this->keys);
    }
}
