<?php namespace Flarum\Extend;

use Illuminate\Foundation\Application;

class ForumAssets implements ExtenderInterface
{
    protected $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function extend(Application $app)
    {
        $app['events']->listen('Flarum\Forum\Events\RenderView', function ($event) {
            $event->assets->addFiles($this->files);
        });
    }
}
