<?php namespace Flarum\Events;

class WillSerializeData
{
    public $action;

    public $data;

    public $request;

    public function __construct($action, &$data, $request)
    {
        $this->action = $action;
        $this->data = &$data;
        $this->request = $request;
    }
}
