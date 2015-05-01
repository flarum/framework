<?php namespace Flarum\Forum\Events;

class RenderView
{
    public $view;

    public $assets;

    public function __construct(&$view, $assets)
    {
        $this->view = &$view;
        $this->assets = $assets;
    }
}
