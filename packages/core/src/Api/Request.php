<?php namespace Flarum\Api;

use Flarum\Support\Actor;
use Illuminate\Http\Request as IlluminateRequest;

class Request
{
    public $input;

    public $actor;

    public $httpRequest;

    public function __construct(array $input, Actor $actor, IlluminateRequest $httpRequest = null)
    {
        $this->input = $input;
        $this->actor = $actor;
        $this->httpRequest = $httpRequest;
    }

    public function get($key, $default = null)
    {
        return isset($this->input[$key]) ? $this->input[$key] : $default;
    }
}
