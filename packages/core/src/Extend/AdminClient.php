<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;
use Flarum\Admin\Actions\IndexAction;

class AdminClient implements ExtenderInterface
{
    protected $assets = [];

    protected $translations = [];

    public function assets($assets)
    {
        $this->assets = array_merge($this->assets, $assets);

        return $this;
    }

    public function translations($keys)
    {
        $this->translations = array_merge($this->translations, $keys);

        return $this;
    }

    public function extend(Container $container)
    {
        $container->make('events')->listen('Flarum\Admin\Events\RenderView', function ($event) {
            $event->assets->addFiles($this->assets);
        });

        IndexAction::$translations = array_merge(IndexAction::$translations, $this->translations);
    }
}
