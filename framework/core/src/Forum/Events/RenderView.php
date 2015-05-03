<?php namespace Flarum\Forum\Events;

class RenderView
{
    public $view;

    public $assets;

    public $action;

    public function __construct(&$view, $assets, $action)
    {
        $this->view = &$view;
        $this->assets = $assets;
        $this->action = $action;
    }
}
