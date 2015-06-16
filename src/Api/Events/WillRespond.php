<?php namespace Flarum\Api\Events;

class WillRespond
{
    public $action;

    public $data;

    public $request;

    public $response;

    public function __construct($action, &$data, $request, $response)
    {
        $this->action = $action;
        $this->data = &$data;
        $this->request = $request;
        $this->response = $response;
    }
}
