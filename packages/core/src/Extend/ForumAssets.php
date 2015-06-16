<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

class ForumAssets implements ExtenderInterface
{
    protected $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function extend(Container $container)
    {
        $container->make('events')->listen('Flarum\Forum\Events\RenderView', function ($event) {
            $event->assets->addFiles($this->files);
        });
    }
}
